<?php

namespace Tests\Feature;

use App\Enums\AppealStatus;
use App\Enums\ClampingStatus;
use App\Enums\CitationStatus;
use App\Enums\ImpoundingStatus;
use App\Models\Archive;
use App\Models\Appeal;
use App\Models\Citation;
use App\Models\CitizenReport;
use App\Models\ClampingRecord;
use App\Models\Payment;
use App\Models\User;
use App\Models\ViolationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'approved',
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'administrator',
            'account_status' => 'approved',
        ]);
    }

    private function citation(array $attributes = []): Citation
    {
        $violation = ViolationType::create([
            'code' => 'TEST',
            'name' => 'Test Violation',
            'penalty_amount' => 500,
            'is_active' => true,
        ]);

        return Citation::create(array_merge([
            'citation_number' => 'CIT-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'violation_type_id' => $violation->id,
            'vehicle_plate' => 'DEL-1234',
            'issued_by' => User::factory()->create(['role' => 'enforcer'])->id,
            'penalty_amount' => 500,
            'status' => CitationStatus::Issued,
            'location' => 'Test Street',
            'issued_at' => now(),
            'due_date' => now()->addDays(7),
        ], $attributes));
    }

    public function test_administrator_cannot_delete_citation(): void
    {
        $citation = $this->citation();

        $this->actingAs($this->admin())
            ->delete("/citations/{$citation->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('citations', ['id' => $citation->id]);
    }

    public function test_super_admin_can_delete_bare_citation(): void
    {
        $citation = $this->citation();

        $this->actingAs($this->superAdmin())
            ->delete("/citations/{$citation->id}")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('citations', ['id' => $citation->id]);
        $this->assertDatabaseHas('archives', [
            'archivable_type' => Citation::class,
            'archivable_id' => $citation->id,
        ]);
    }

    public function test_citation_with_payment_cannot_be_deleted(): void
    {
        $superAdmin = $this->superAdmin();
        $citation = $this->citation();

        Payment::create([
            'receipt_number' => 'RCPT-000001',
            'citation_id' => $citation->id,
            'cashier_id' => $superAdmin->id,
            'amount' => 500,
            'payment_method' => 'cash',
            'paid_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->delete("/citations/{$citation->id}")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('citations', ['id' => $citation->id]);
    }

    public function test_approved_appeal_cannot_be_deleted(): void
    {
        $superAdmin = $this->superAdmin();
        $citation = $this->citation();

        $appeal = Appeal::create([
            'citation_id' => $citation->id,
            'submitted_by' => $superAdmin->id,
            'reason' => 'Contested violation',
            'status' => AppealStatus::Approved,
            'submitted_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->delete("/appeals/{$appeal->id}")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('appeals', ['id' => $appeal->id]);
    }

    public function test_submitted_appeal_can_be_deleted(): void
    {
        $superAdmin = $this->superAdmin();
        $citation = $this->citation();

        $appeal = Appeal::create([
            'citation_id' => $citation->id,
            'submitted_by' => $superAdmin->id,
            'reason' => 'Contested violation',
            'status' => AppealStatus::Submitted,
            'submitted_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->delete("/appeals/{$appeal->id}")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('appeals', ['id' => $appeal->id]);
        $this->assertDatabaseHas('archives', [
            'archivable_type' => Appeal::class,
            'archivable_id' => $appeal->id,
        ]);
    }

    public function test_payment_for_released_clamp_cannot_be_deleted(): void
    {
        $superAdmin = $this->superAdmin();
        $citation = $this->citation();

        $clamping = ClampingRecord::create([
            'notice_number' => 'CLMP-000001',
            'citation_id' => $citation->id,
            'clamped_by' => $superAdmin->id,
            'status' => ClampingStatus::Released,
            'clamped_at' => now()->subDays(2),
        ]);

        $payment = Payment::create([
            'receipt_number' => 'RCPT-000002',
            'payable_type' => ClampingRecord::class,
            'payable_id' => $clamping->id,
            'cashier_id' => $superAdmin->id,
            'amount' => 300,
            'payment_method' => 'cash',
            'paid_at' => now()->subDays(2),
        ]);

        $this->actingAs($superAdmin)
            ->delete("/payments/{$payment->id}")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('payments', ['id' => $payment->id]);
    }

    public function test_payment_delete_reverts_clamp_to_awaiting_payment(): void
    {
        $superAdmin = $this->superAdmin();
        $citation = $this->citation();

        $clamping = ClampingRecord::create([
            'notice_number' => 'CLMP-000002',
            'citation_id' => $citation->id,
            'clamped_by' => $superAdmin->id,
            'status' => ClampingStatus::Paid,
            'clamped_at' => now()->subDay(),
        ]);

        $payment = Payment::create([
            'receipt_number' => 'RCPT-000003',
            'payable_type' => ClampingRecord::class,
            'payable_id' => $clamping->id,
            'cashier_id' => $superAdmin->id,
            'amount' => 300,
            'payment_method' => 'cash',
            'paid_at' => now()->subDay(),
        ]);

        $this->actingAs($superAdmin)
            ->delete("/payments/{$payment->id}")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
        $this->assertSame(
            ClampingStatus::AwaitingPayment->value,
            $clamping->fresh()->status->value
        );
        $this->assertDatabaseHas('archives', [
            'archivable_type' => Payment::class,
            'archivable_id' => $payment->id,
        ]);
    }

    public function test_impounding_delete_reverts_to_awaiting_payment(): void
    {
        $superAdmin = $this->superAdmin();
        $citation = $this->citation();

        $impounding = \App\Models\ImpoundingRecord::create([
            'notice_number' => 'IMP-000001',
            'citation_id' => $citation->id,
            'impounded_by' => $superAdmin->id,
            'status' => ImpoundingStatus::Paid,
            'towing_fee' => 1500,
            'storage_fee_per_day' => 100,
            'admin_fee' => 200,
            'grace_until' => now()->addDay(),
            'impounded_at' => now()->subDays(2),
            'paid_at' => now()->subDay(),
        ]);

        $payment = Payment::create([
            'receipt_number' => 'RCPT-000004',
            'payable_type' => \App\Models\ImpoundingRecord::class,
            'payable_id' => $impounding->id,
            'cashier_id' => $superAdmin->id,
            'amount' => 1800,
            'payment_method' => 'cash',
            'paid_at' => now()->subDay(),
        ]);

        $this->actingAs($superAdmin)
            ->delete("/payments/{$payment->id}")
            ->assertRedirect()
            ->assertSessionHas('success');

        $impounding->refresh();
        $this->assertSame(ImpoundingStatus::AwaitingPayment->value, $impounding->status->value);
        $this->assertNull($impounding->paid_at);
    }

    public function test_citizen_report_with_citation_cannot_be_deleted(): void
    {
        $superAdmin = $this->superAdmin();
        $citation = $this->citation();

        $report = CitizenReport::create([
            'report_number' => 'CR-000001',
            'requester_name' => 'Juan Dela Cruz',
            'requester_phone' => '09171234567',
            'requester_email' => 'juan@example.com',
            'evidence_photo' => 'reports/evidence.jpg',
            'vehicle_plate' => 'DEL-1234',
            'location_address' => 'Test Street',
            'latitude' => 10.2459123,
            'longitude' => 123.7955462,
            'status' => 'resolved',
            'citation_id' => $citation->id,
        ]);

        $this->actingAs($superAdmin)
            ->delete("/citizen-reports/{$report->id}")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('citizen_reports', ['id' => $report->id]);
    }

    public function test_user_with_history_cannot_be_deleted(): void
    {
        $superAdmin = $this->superAdmin();
        $enforcer = User::factory()->create(['role' => 'enforcer', 'account_status' => 'approved']);

        $this->citation(['issued_by' => $enforcer->id]);

        $this->actingAs($superAdmin)
            ->delete("/users/{$enforcer->id}")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $enforcer->id]);
    }

    public function test_user_without_history_can_be_deleted(): void
    {
        $superAdmin = $this->superAdmin();
        $cashier = User::factory()->create(['role' => 'cashier', 'account_status' => 'approved']);

        $this->actingAs($superAdmin)
            ->delete("/users/{$cashier->id}")
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $cashier->id]);
        $this->assertDatabaseHas('archives', [
            'archivable_type' => User::class,
            'archivable_id' => $cashier->id,
        ]);
    }

    public function test_super_admin_cannot_delete_own_account(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->delete("/users/{$superAdmin->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
    }
}
