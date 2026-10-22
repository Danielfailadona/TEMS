<?php

namespace Tests\Feature;

use App\Enums\CitationStatus;
use App\Models\Citation;
use App\Models\Payment;
use App\Models\User;
use App\Models\ViolationType;
use App\Services\UserKpiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierKpiAndOverdueTest extends TestCase
{
    use RefreshDatabase;

    private function violationType(): ViolationType
    {
        return ViolationType::firstOrCreate(
            ['code' => 'TEST-VIO'],
            ['name' => 'Test Violation', 'penalty_amount' => 500, 'is_active' => true]
        );
    }

    private function citation(User $enforcer, array $overrides = []): Citation
    {
        return Citation::create(array_merge([
            'citation_number' => 'CIT-'.uniqid(),
            'violation_type_id' => $this->violationType()->id,
            'issued_by' => $enforcer->id,
            'penalty_amount' => 500,
            'status' => CitationStatus::Issued->value,
            'location' => 'Test Street',
            'vehicle_plate' => 'ABC-1234',
            'issued_at' => now(),
            'due_date' => now()->addDays(7),
        ], $overrides));
    }

    private function payment(Citation $citation, User $cashier, array $overrides = []): Payment
    {
        return Payment::create(array_merge([
            'receipt_number' => 'RCPT-'.uniqid(),
            'citation_id' => $citation->id,
            'cashier_id' => $cashier->id,
            'amount' => 500,
            'payment_method' => 'cash',
            'paid_at' => now(),
        ], $overrides));
    }

    public function test_cashier_gets_dedicated_cashier_dashboard(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $response = $this->actingAs($cashier)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Record Payment');
        $response->assertSee('My Contribution');
        $response->assertSee('Overdue Citations');
    }

    public function test_cashier_contribution_reflects_their_payments(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $enforcer = User::factory()->create(['role' => 'enforcer']);

        $this->payment($this->citation($enforcer), $cashier, ['amount' => 300]);
        $this->payment($this->citation($enforcer), $cashier, ['amount' => 200]);

        // A payment recorded by someone else must not count.
        $other = User::factory()->create(['role' => 'cashier']);
        $this->payment($this->citation($enforcer), $other, ['amount' => 999]);

        $kpi = app(UserKpiService::class)->contribution($cashier);

        $this->assertSame('My Contribution', $kpi['scope']);

        $paymentsCard = collect($kpi['cards'])->firstWhere('label', 'Payments Recorded');
        $amountCard = collect($kpi['cards'])->firstWhere('label', 'Amount Collected');

        $this->assertSame('2', $paymentsCard['value']);
        $this->assertSame('₱500.00', $amountCard['value']);
    }

    public function test_enforcer_contribution_reflects_citations_issued(): void
    {
        $enforcer = User::factory()->create(['role' => 'enforcer']);
        $this->citation($enforcer);
        $this->citation($enforcer);

        $kpi = app(UserKpiService::class)->contribution($enforcer);

        $card = collect($kpi['cards'])->firstWhere('label', 'Citations Issued');
        $this->assertSame('2', $card['value']);
    }

    public function test_admin_contribution_is_system_wide(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $enforcer = User::factory()->create(['role' => 'enforcer']);
        $cashier = User::factory()->create(['role' => 'cashier']);
        $citation = $this->citation($enforcer);
        $this->payment($citation, $cashier);

        $kpi = app(UserKpiService::class)->contribution($admin);

        $this->assertSame('System-wide', $kpi['scope']);
        $this->assertSame('1', collect($kpi['cards'])->firstWhere('label', 'Citations Issued')['value']);
        $this->assertSame('1', collect($kpi['cards'])->firstWhere('label', 'Payments Recorded')['value']);
    }

    public function test_cashier_can_view_overdue_citations_report(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $enforcer = User::factory()->create(['role' => 'enforcer']);
        $this->citation($enforcer, ['due_date' => now()->subDays(10)]);

        $response = $this->actingAs($cashier)->get('/reports/overdue-citations');

        $response->assertOk();
        $response->assertSee('Overdue Citation Details');
        $response->assertSee('8-30 days overdue');
    }

    public function test_enforcer_cannot_view_overdue_citations_report(): void
    {
        $enforcer = User::factory()->create(['role' => 'enforcer']);

        $this->actingAs($enforcer)->get('/reports/overdue-citations')->assertForbidden();
    }

    public function test_overdue_report_excludes_paid_and_not_yet_due_citations(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $enforcer = User::factory()->create(['role' => 'enforcer']);

        $this->citation($enforcer, ['citation_number' => 'CIT-OVERDUE', 'due_date' => now()->subDays(3)]);
        $this->citation($enforcer, ['citation_number' => 'CIT-FUTURE', 'due_date' => now()->addDays(3)]);
        $this->citation($enforcer, [
            'citation_number' => 'CIT-PAID',
            'due_date' => now()->subDays(3),
            'status' => CitationStatus::Paid->value,
        ]);

        $response = $this->actingAs($cashier)->get('/reports/overdue-citations');

        $response->assertOk();
        $response->assertSee('CIT-OVERDUE');
        $response->assertDontSee('CIT-FUTURE');
        $response->assertDontSee('CIT-PAID');
    }
}
