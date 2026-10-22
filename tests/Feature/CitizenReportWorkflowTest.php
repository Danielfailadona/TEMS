<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\Citation;
use App\Models\CitizenReport;
use App\Models\User;
use App\Models\ViolationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CitizenReportWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function enforcer(string $email = 'enforcer@example.com'): User
    {
        return User::factory()->create([
            'email' => $email,
            'role' => 'enforcer',
            'is_available' => true,
            'is_online' => true,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'administrator',
        ]);
    }

    private function report(array $overrides = []): CitizenReport
    {
        return CitizenReport::create(array_merge([
            'report_number' => 'RPT-TEST-0001',
            'requester_name' => 'Juan Dela Cruz',
            'requester_phone' => '+639123456789',
            'requester_email' => 'juan@example.com',
            'location_address' => 'Test Street',
            'latitude' => 15.4872,
            'longitude' => 120.9762,
            'vehicle_plate' => 'ABC-1234',
            'vehicle_make' => 'Toyota',
            'vehicle_model' => 'Vios',
            'vehicle_color' => 'White',
            'evidence_photo' => 'citizen-reports/test.jpg',
            'status' => ReportStatus::Pending->value,
        ], $overrides));
    }

    private function violationType(): ViolationType
    {
        return ViolationType::firstOrCreate(
            ['code' => 'TEST-VIO'],
            [
                'name' => 'Test Violation',
                'penalty_amount' => 500,
                'is_active' => true,
            ]
        );
    }

    private function fakeStorage(): void
    {
        config([
            'supabase.url' => 'https://example.supabase.co',
            'supabase.service_role_key' => 'test-service-role-key',
            'supabase.bucket' => 'evidence',
        ]);

        Http::fake(['*' => Http::response('', 200)]);
    }

    public function test_enforcer_can_claim_a_pending_report(): void
    {
        $report = $this->report();

        $this->actingAs($this->enforcer())
            ->post("/citizen-reports/{$report->id}/claim")
            ->assertRedirect();

        $report->refresh();
        $this->assertSame(ReportStatus::Accepted->value, $report->status);
        $this->assertSame('enforcer@example.com', $report->assignedTo->email);
    }

    public function test_enforcer_can_reject_a_report_with_reason(): void
    {
        $enforcer = $this->enforcer();
        $report = $this->report(['assigned_to' => $enforcer->id, 'status' => ReportStatus::Accepted->value]);

        $this->actingAs($enforcer)
            ->post("/citizen-reports/{$report->id}/reject", ['rejection_reason' => 'Not a parking violation'])
            ->assertRedirect();

        $report->refresh();
        $this->assertSame(ReportStatus::Rejected->value, $report->status);
        $this->assertSame('Not a parking violation', $report->rejection_reason);
    }

    public function test_enforcer_issues_citation_from_report_and_links_it(): void
    {
        $this->fakeStorage();
        $enforcer = $this->enforcer();
        $report = $this->report(['assigned_to' => $enforcer->id, 'status' => ReportStatus::Accepted->value]);
        $violationType = $this->violationType();

        $this->actingAs($enforcer)
            ->post("/citizen-reports/{$report->id}/issue-citation", [
                'violation_type_id' => $violationType->id,
                'vehicle_plate' => 'ABC-1234',
                'location' => 'Test Street',
                'evidence' => [UploadedFile::fake()->image('evidence.jpg')],
            ]);

        $this->assertDatabaseCount('citations', 1);

        $citation = Citation::firstOrFail();
        $report->refresh();

        $this->assertSame($citation->id, $report->citation_id);
        $this->assertSame(ReportStatus::Resolved->value, $report->status);
        $this->assertSame('citation_issued', $report->resolution_type);
    }

    public function test_duplicate_citation_issue_is_rejected(): void
    {
        $this->fakeStorage();
        $enforcer = $this->enforcer();
        $citation = Citation::create([
            'citation_number' => 'CIT-EXISTING',
            'violation_type_id' => $this->violationType()->id,
            'issued_by' => $enforcer->id,
            'penalty_amount' => 500,
            'status' => \App\Enums\CitationStatus::Issued->value,
            'location' => 'Test Street',
            'vehicle_plate' => 'ABC-1234',
            'issued_at' => now(),
            'due_date' => now()->addDays(7),
        ]);
        $report = $this->report([
            'assigned_to' => $enforcer->id,
            'status' => ReportStatus::Accepted->value,
            'citation_id' => $citation->id,
        ]);

        $this->actingAs($enforcer)
            ->post("/citizen-reports/{$report->id}/issue-citation", [
                'violation_type_id' => $this->violationType()->id,
                'vehicle_plate' => 'ABC-1234',
                'location' => 'Test Street',
                'evidence' => [UploadedFile::fake()->image('evidence.jpg')],
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('citations', 1);
    }

    public function test_enforcer_can_resolve_a_report_with_notes(): void
    {
        $enforcer = $this->enforcer();
        $report = $this->report(['assigned_to' => $enforcer->id, 'status' => ReportStatus::Accepted->value]);

        $this->actingAs($enforcer)
            ->post("/citizen-reports/{$report->id}/resolve", ['resolution_notes' => 'Owner warned in person.'])
            ->assertRedirect();

        $report->refresh();
        $this->assertSame(ReportStatus::Resolved->value, $report->status);
        $this->assertSame('resolved_with_notes', $report->resolution_type);
        $this->assertSame('Owner warned in person.', $report->resolution_notes);
    }

    public function test_enforcer_cannot_act_on_unassigned_other_report(): void
    {
        $enforcer = $this->enforcer();
        $other = $this->enforcer('other-enforcer@example.com');
        $report = $this->report(['assigned_to' => $other->id, 'status' => ReportStatus::Accepted->value]);

        $this->actingAs($enforcer)
            ->post("/citizen-reports/{$report->id}/resolve", ['resolution_notes' => 'Nope'])
            ->assertForbidden();

        $this->assertSame(ReportStatus::Accepted->value, $report->fresh()->status);
    }
}
