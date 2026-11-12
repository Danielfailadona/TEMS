<?php

namespace Tests\Feature;

use App\Enums\AppealStatus;
use App\Enums\CitationStatus;
use App\Models\Appeal;
use App\Models\Citation;
use App\Models\User;
use App\Models\ViolationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppealsPageTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => 'super_admin',
            'account_status' => 'approved',
        ]);
    }

    private function appealWithCitation(): Appeal
    {
        $violation = ViolationType::create([
            'code' => 'TEST',
            'name' => 'Test Violation',
            'penalty_amount' => 500,
            'is_active' => true,
        ]);

        $citation = Citation::create([
            'citation_number' => 'CIT-APL-000001',
            'violation_type_id' => $violation->id,
            'vehicle_plate' => 'APL-1234',
            'issued_by' => User::factory()->create(['role' => 'enforcer'])->id,
            'penalty_amount' => 500,
            'status' => CitationStatus::Issued,
            'location' => 'Test Street',
            'issued_at' => now(),
            'due_date' => now()->addDays(7),
        ]);

        return Appeal::create([
            'citation_id' => $citation->id,
            'submitted_by' => User::factory()->create(['role' => 'vehicle_owner'])->id,
            'reason' => 'Contested violation',
            'status' => AppealStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }

    public function test_appeals_index_renders(): void
    {
        $admin = $this->superAdmin();
        $appeal = $this->appealWithCitation();

        $response = $this->actingAs($admin)->get('/appeals');

        $response->assertOk();
        $response->assertSee($appeal->ticketNumber());
    }

    public function test_appeals_show_renders(): void
    {
        $admin = $this->superAdmin();
        $appeal = $this->appealWithCitation();

        $response = $this->actingAs($admin)->get('/appeals/'.$appeal->id);

        $response->assertOk();
        $response->assertSee($appeal->ticketNumber());
        $response->assertSee('Contested violation');
    }
}
