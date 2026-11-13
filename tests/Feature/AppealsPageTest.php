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

    public function test_appeals_create_search_and_review(): void
    {
        $admin = $this->superAdmin();
        $violation = ViolationType::create([
            'code' => 'TEST',
            'name' => 'Test Violation',
            'penalty_amount' => 500,
            'is_active' => true,
        ]);

        $match = Citation::create([
            'citation_number' => 'CIT-MATCH-0001',
            'violation_type_id' => $violation->id,
            'vehicle_plate' => 'MTCH-111',
            'issued_by' => User::factory()->create(['role' => 'enforcer'])->id,
            'penalty_amount' => 500,
            'status' => CitationStatus::Issued,
            'location' => 'Test Street',
            'issued_at' => now(),
            'due_date' => now()->addDays(7),
        ]);

        Citation::create([
            'citation_number' => 'CIT-OTHER-0002',
            'violation_type_id' => $violation->id,
            'vehicle_plate' => 'OTH-222',
            'issued_by' => User::factory()->create(['role' => 'enforcer'])->id,
            'penalty_amount' => 500,
            'status' => CitationStatus::Issued,
            'location' => 'Test Street',
            'issued_at' => now(),
            'due_date' => now()->addDays(7),
        ]);

        // Search narrows the results table
        $search = $this->actingAs($admin)->get('/appeals/create?search=MTCH-111');
        $search->assertOk();
        $search->assertSee('CIT-MATCH-0001');
        $search->assertDontSee('CIT-OTHER-0002');

        // Selecting a citation (server-side) renders the review panel + form
        $review = $this->actingAs($admin)->get('/appeals/create?citation_id='.$match->id);
        $review->assertOk();
        $review->assertSee('Selected Citation — Review');
        $review->assertSee('CIT-MATCH-0001');
        $review->assertSee('Next Step — File the Appeal');
    }
}
