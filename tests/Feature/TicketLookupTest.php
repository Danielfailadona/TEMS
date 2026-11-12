<?php

namespace Tests\Feature;

use App\Enums\CitationStatus;
use App\Models\Citation;
use App\Models\ClampingRecord;
use App\Models\User;
use App\Models\ViolationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketLookupTest extends TestCase
{
    use RefreshDatabase;

    private function citationWithPlate(string $plate, string $number = 'CIT-LKP-000001'): Citation
    {
        $violation = ViolationType::create([
            'code' => 'TEST',
            'name' => 'Test Violation',
            'penalty_amount' => 500,
            'is_active' => true,
        ]);

        return Citation::create([
            'citation_number' => $number,
            'violation_type_id' => $violation->id,
            'vehicle_plate' => $plate,
            'issued_by' => User::factory()->create(['role' => 'enforcer'])->id,
            'penalty_amount' => 500,
            'status' => CitationStatus::Issued,
            'location' => 'Test Street',
            'issued_at' => now(),
            'due_date' => now()->addDays(7),
        ]);
    }

    public function test_simple_lookup_matches_plate_case_insensitively(): void
    {
        $this->citationWithPlate('ABC-1234');

        // Live servers run Postgres, where LIKE is case-sensitive — a lowercase
        // plate typed by a citizen must still match the uppercase stored plate.
        $response = $this->get('/citizen/citation/search?search=abc-1234');

        $response->assertOk();
        $response->assertSee('CIT-LKP-000001');
    }

    public function test_advanced_search_finds_by_plate(): void
    {
        $this->citationWithPlate('XYZ-9999');

        $response = $this->get('/citizen/citation/search?plate_number=xyz-9999');

        $response->assertOk();
        $response->assertSee('CIT-LKP-000001');
    }

    public function test_advanced_search_finds_by_number_status_and_date(): void
    {
        $this->citationWithPlate('DEF-4567');

        $response = $this->get('/citizen/citation/search?citation_number=cit-lkp-000001&status=issued&issued_date='.now()->toDateString());

        $response->assertOk();
        $response->assertSee('CIT-LKP-000001');
    }

    public function test_advanced_search_without_matches_redirects_with_error(): void
    {
        $this->citationWithPlate('ABC-1234');

        $response = $this->get('/citizen/citation/search?plate_number=ZZZ-0000');

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_clamping_search_finds_by_plate_via_citation(): void
    {
        $citation = $this->citationWithPlate('GHI-7890');

        ClampingRecord::create([
            'notice_number' => 'CLMP-LKP-0001',
            'citation_id' => $citation->id,
            'clamped_by' => User::factory()->create(['role' => 'enforcer'])->id,
            'status' => 'awaiting_payment',
            'clamped_at' => now(),
        ]);

        $response = $this->get('/citizen/clamping/search?search=ghi-7890');

        $response->assertOk();
        $response->assertSee('CLMP-LKP-0001');
    }
}
