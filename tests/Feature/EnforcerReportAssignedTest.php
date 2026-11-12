<?php

namespace Tests\Feature;

use App\Models\CitizenReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnforcerReportAssignedTest extends TestCase
{
    use RefreshDatabase;

    private function enforcer(): User
    {
        return User::factory()->create([
            'role' => 'enforcer',
            'account_status' => 'approved',
        ]);
    }

    private function report(array $attributes = []): CitizenReport
    {
        return CitizenReport::create(array_merge([
            'report_number' => 'CR-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'requester_name' => 'Juan Dela Cruz',
            'requester_phone' => '09171234567',
            'requester_email' => 'juan@example.com',
            'evidence_photo' => 'reports/evidence.jpg',
            'vehicle_plate' => 'RPT-1234',
            'location_address' => 'Test Street',
            'latitude' => 10.2459123,
            'longitude' => 123.7955462,
            'status' => 'accepted',
        ], $attributes));
    }

    public function test_enforcer_index_lists_only_assigned_reports(): void
    {
        $enforcer = $this->enforcer();
        $assigned = $this->report(['report_number' => 'CR-000001', 'assigned_to' => $enforcer->id]);
        $this->report(['report_number' => 'CR-000002', 'status' => 'pending']);

        $response = $this->actingAs($enforcer)->get('/citizen-reports');

        $response->assertOk();
        $response->assertSee('CR-000001');
        $response->assertDontSee('CR-000002');
        $response->assertSee('hero-card-list');
    }

    public function test_admin_index_keeps_table_without_hero_cards(): void
    {
        $admin = User::factory()->create(['role' => 'administrator', 'account_status' => 'approved']);
        $this->report(['report_number' => 'CR-000007', 'assigned_to' => $this->enforcer()->id]);

        $response = $this->actingAs($admin)->get('/citizen-reports');

        $response->assertOk();
        $response->assertDontSee('hero-card-list');
        $response->assertSee('CR-000007');
    }

    public function test_enforcer_citations_page_renders_hero_cards(): void
    {
        $enforcer = $this->enforcer();

        $response = $this->actingAs($enforcer)->get('/citations');

        $response->assertOk();
        $response->assertSee('hero-card-list');
    }

    public function test_admin_citations_page_keeps_table_without_hero_cards(): void
    {
        $admin = User::factory()->create(['role' => 'administrator', 'account_status' => 'approved']);

        $response = $this->actingAs($admin)->get('/citations');

        $response->assertOk();
        $response->assertDontSee('hero-card-list');
    }

    public function test_enforcer_cannot_claim_pending_reports(): void
    {
        $enforcer = $this->enforcer();
        $pending = $this->report(['report_number' => 'CR-000003', 'status' => 'pending']);

        $response = $this->actingAs($enforcer)->post("/citizen-reports/{$pending->id}/claim");

        $response->assertForbidden();
        $this->assertSame('pending', $pending->fresh()->status);
    }

    public function test_enforcer_cannot_view_unassigned_report(): void
    {
        $enforcer = $this->enforcer();
        $report = $this->report(['report_number' => 'CR-000005']);

        $response = $this->actingAs($enforcer)->get("/citizen-reports/{$report->id}");

        $response->assertForbidden();
    }

    public function test_enforcer_show_page_hides_admin_actions(): void
    {
        $enforcer = $this->enforcer();
        $report = $this->report(['report_number' => 'CR-000004', 'assigned_to' => $enforcer->id]);

        $response = $this->actingAs($enforcer)->get("/citizen-reports/{$report->id}");

        $response->assertOk();
        $response->assertSee('Issue Citation');
        $response->assertSee('Resolve with Notes');
        $response->assertDontSee('Accept &amp; Assign');
        $response->assertDontSee('Reassign');
        $response->assertDontSee('Reject Report');
    }

    public function test_admin_show_page_keeps_admin_actions(): void
    {
        $admin = User::factory()->create(['role' => 'administrator', 'account_status' => 'approved']);
        $enforcer = $this->enforcer();
        $report = $this->report(['report_number' => 'CR-000006', 'assigned_to' => $enforcer->id]);

        $response = $this->actingAs($admin)->get("/citizen-reports/{$report->id}");

        $response->assertOk();
        $response->assertSee('Reassign');
        $response->assertSee('Back');
    }
}
