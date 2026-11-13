<?php

namespace Tests\Feature;

use App\Enums\AppealStatus;
use App\Enums\CitationStatus;
use App\Models\Appeal;
use App\Models\Citation;
use App\Models\Payment;
use App\Models\User;
use App\Models\ViolationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppealDiscountTest extends TestCase
{
    use RefreshDatabase;

    private const PENALTY = 500.0;

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'approved',
        ]);
    }

    private function citation(CitationStatus $status = CitationStatus::Issued): Citation
    {
        $violation = ViolationType::create([
            'code' => 'TEST',
            'name' => 'Test Violation',
            'penalty_amount' => self::PENALTY,
            'is_active' => true,
        ]);

        return Citation::create([
            'citation_number' => 'CIT-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'violation_type_id' => $violation->id,
            'vehicle_plate' => 'APL-1234',
            'issued_by' => User::factory()->create(['role' => 'enforcer'])->id,
            'penalty_amount' => self::PENALTY,
            'status' => $status,
            'location' => 'Test Street',
            'issued_at' => now(),
            'due_date' => now()->addDays(7),
        ]);
    }

    private function appeal(Citation $citation, int $submitterId): Appeal
    {
        return Appeal::create([
            'citation_id' => $citation->id,
            'submitted_by' => $submitterId,
            'reason' => 'Contested violation',
            'status' => AppealStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }

    public function test_approved_appeal_applies_15_percent_discount_to_unpaid_citation(): void
    {
        $reviewer = $this->superAdmin();
        $submitter = User::factory()->create(['role' => 'vehicle_owner']);
        $citation = $this->citation();
        $appeal = $this->appeal($citation, $submitter->id);

        $this->actingAs($reviewer)
            ->patch("/appeals/{$appeal->id}", [
                'status' => 'approved',
                'decision_notes' => 'Valid appeal',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $citation->refresh();
        $appeal->refresh();

        $expected = round(self::PENALTY * (1 - Appeal::APPROVED_DISCOUNT_PERCENT / 100), 2);

        $this->assertSame($expected, (float) $citation->penalty_amount);
        $this->assertSame(self::PENALTY, (float) $appeal->original_amount);
        $this->assertSame($expected, (float) $appeal->adjusted_amount);
        $this->assertSame(AppealStatus::Approved, $appeal->status);
        $this->assertSame(425.0, (float) $citation->penalty_amount);
    }

    public function test_approved_appeal_leaves_paid_citation_unchanged(): void
    {
        $reviewer = $this->superAdmin();
        $submitter = User::factory()->create(['role' => 'vehicle_owner']);
        $citation = $this->citation(CitationStatus::Paid);
        $appeal = $this->appeal($citation, $submitter->id);

        $this->actingAs($reviewer)
            ->patch("/appeals/{$appeal->id}", ['status' => 'approved'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $citation->refresh();
        $appeal->refresh();

        $this->assertSame(self::PENALTY, (float) $citation->penalty_amount);
        $this->assertSame(AppealStatus::Approved, $appeal->status);
    }

    public function test_rejected_appeal_keeps_original_penalty(): void
    {
        $reviewer = $this->superAdmin();
        $submitter = User::factory()->create(['role' => 'vehicle_owner']);
        $citation = $this->citation();
        $appeal = $this->appeal($citation, $submitter->id);

        $this->actingAs($reviewer)
            ->patch("/appeals/{$appeal->id}", [
                'status' => 'rejected',
                'decision_notes' => 'Not valid',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $citation->refresh();
        $appeal->refresh();

        $this->assertSame(self::PENALTY, (float) $citation->penalty_amount);
        $this->assertSame(AppealStatus::Rejected, $appeal->status);
    }

    public function test_record_payment_uses_discounted_amount(): void
    {
        $reviewer = $this->superAdmin();
        $submitter = User::factory()->create(['role' => 'vehicle_owner']);
        $citation = $this->citation();
        $appeal = $this->appeal($citation, $submitter->id);

        $this->actingAs($reviewer)
            ->patch("/appeals/{$appeal->id}", ['status' => 'approved'])
            ->assertRedirect();

        $response = $this->actingAs($reviewer)
            ->get('/payments/create?citation_id='.$citation->id);

        $response->assertOk();
        $response->assertSee('425.00');
        $response->assertDontSee('500.00');
    }

    public function test_approved_appeal_voids_stale_pending_checkout(): void
    {
        $reviewer = $this->superAdmin();
        $submitter = User::factory()->create(['role' => 'vehicle_owner']);
        $citation = $this->citation();
        $appeal = $this->appeal($citation, $submitter->id);

        // An abandoned online checkout for the pre-discount amount.
        $stale = Payment::create([
            'receipt_number' => 'RCPT-STALE-0001',
            'citation_id' => $citation->id,
            'cashier_id' => $reviewer->id,
            'amount' => 500,
            'payment_method' => 'other',
            'online_payment_method' => 'gcash',
            'paymongo_checkout_id' => 'cs_test_stale',
            'paid_at' => null,
        ]);

        $this->actingAs($reviewer)
            ->patch("/appeals/{$appeal->id}", ['status' => 'approved'])
            ->assertRedirect();

        $this->assertDatabaseMissing('payments', ['id' => $stale->id]);
        $this->assertSame(425.0, (float) $citation->fresh()->penalty_amount);
    }

    public function test_appeal_show_labels_pending_checkout_without_receipt(): void
    {
        $reviewer = $this->superAdmin();
        $submitter = User::factory()->create(['role' => 'vehicle_owner']);
        $citation = $this->citation();
        $appeal = $this->appeal($citation, $submitter->id);

        Payment::create([
            'receipt_number' => 'RCPT-PENDING-0001',
            'citation_id' => $citation->id,
            'cashier_id' => $reviewer->id,
            'amount' => 500,
            'payment_method' => 'other',
            'online_payment_method' => 'gcash',
            'paymongo_checkout_id' => 'cs_test_pending',
            'paid_at' => null,
        ]);

        $response = $this->actingAs($reviewer)->get("/appeals/{$appeal->id}");

        $response->assertOk();
        $response->assertSee('Pending Online Payment');
        $response->assertDontSee('Receipt #');
    }
}
