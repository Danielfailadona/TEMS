<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ViolationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EvidenceRequiredTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'password' => bcrypt('password'),
            'role' => 'administrator',
        ]);
    }

    private function violationType(): ViolationType
    {
        return ViolationType::create([
            'code' => 'TEST-VIO',
            'name' => 'Test Violation',
            'penalty_amount' => 500,
            'is_active' => true,
        ]);
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

    public function test_citation_requires_evidence_photo(): void
    {
        $violationType = $this->violationType();

        $response = $this->actingAs($this->admin())
            ->from('/citations/create')
            ->post('/citations', [
                'violation_type_id' => $violationType->id,
                'vehicle_plate' => 'ABC-1234',
                'location' => 'Test Street',
            ]);

        $response->assertSessionHasErrors('evidence');
        $this->assertDatabaseCount('citations', 0);
    }

    public function test_others_violation_requires_description_and_penalty(): void
    {
        $others = ViolationType::create([
            'code' => 'others',
            'name' => 'Others',
            'penalty_amount' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin())
            ->from('/citations/create')
            ->post('/citations', [
                'violation_type_id' => $others->id,
                'vehicle_plate' => 'ABC-1234',
                'location' => 'Test Street',
                'evidence' => [UploadedFile::fake()->image('evidence.jpg')],
            ]);

        $response->assertSessionHasErrors(['custom_violation_name', 'penalty_amount']);
        $this->assertDatabaseCount('citations', 0);
    }

    public function test_others_violation_stores_custom_name_and_penalty(): void
    {
        $this->fakeStorage();

        $others = ViolationType::create([
            'code' => 'others',
            'name' => 'Others',
            'penalty_amount' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->post('/citations', [
                'violation_type_id' => $others->id,
                'custom_violation_name' => 'Illegal use of horn',
                'penalty_amount' => 750,
                'vehicle_plate' => 'ABC-1234',
                'location' => 'Test Street',
                'evidence' => [UploadedFile::fake()->image('evidence.jpg')],
            ]);

        $citation = \App\Models\Citation::firstOrFail();
        $this->assertSame('Illegal use of horn', $citation->custom_violation_name);
        $this->assertSame('Illegal use of horn', $citation->violationLabel());
        $this->assertEquals(750.0, (float) $citation->penalty_amount);
        $this->assertTrue($citation->isCustomViolation());
    }

    public function test_citation_is_created_with_evidence_photo(): void
    {
        $this->fakeStorage();
        $violationType = $this->violationType();

        $this->actingAs($this->admin())
            ->post('/citations', [
                'violation_type_id' => $violationType->id,
                'vehicle_plate' => 'ABC-1234',
                'location' => 'Test Street',
                'evidence' => [UploadedFile::fake()->image('evidence.jpg')],
            ]);

        $this->assertDatabaseCount('citations', 1);
        $this->assertDatabaseCount('citation_evidence', 1);
    }

    public function test_impoundable_violation_does_not_auto_impound(): void
    {
        $this->fakeStorage();

        $violationType = ViolationType::create([
            'code' => 'unregistered',
            'name' => 'Unregistered Vehicle',
            'penalty_amount' => 10000,
            'is_active' => true,
            'is_impoundable' => true,
        ]);

        $this->actingAs($this->admin())
            ->post('/citations', [
                'violation_type_id' => $violationType->id,
                'vehicle_plate' => 'IMP-1234',
                'location' => 'Test Street',
                'evidence' => [UploadedFile::fake()->image('evidence.jpg')],
            ]);

        $this->assertDatabaseCount('citations', 1);
        $this->assertDatabaseCount('impounding_records', 0);

        // The citation stays Issued — impounding is a manual admin decision.
        $citation = \App\Models\Citation::firstOrFail();
        $this->assertSame(\App\Enums\CitationStatus::Issued, $citation->status);
    }

    public function test_impoundable_citation_appears_as_eligible_on_impounding_create(): void
    {
        $this->fakeStorage();

        $violationType = ViolationType::create([
            'code' => 'unregistered',
            'name' => 'Unregistered Vehicle',
            'penalty_amount' => 10000,
            'is_active' => true,
            'is_impoundable' => true,
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/citations', [
                'violation_type_id' => $violationType->id,
                'vehicle_plate' => 'IMP-1234',
                'location' => 'Test Street',
                'evidence' => [UploadedFile::fake()->image('evidence.jpg')],
            ]);

        $response = $this->actingAs($admin)->get('/impounding/create');

        $response->assertOk();
        $response->assertSee('IMP-1234');
        $response->assertSee('Impound Vehicle');

        // Selecting a citation (server-side, ?citation_id=) shows the review panel
        // with details, fees, and the next-step record form.
        $citation = \App\Models\Citation::where('vehicle_plate', 'IMP-1234')->firstOrFail();
        $review = $this->actingAs($admin)->get('/impounding/create?citation_id='.$citation->id);

        $review->assertOk();
        $review->assertSee('Selected Citation — Review');
        $review->assertSee('IMP-1234');
        $review->assertSee('Next Step — Record the Impound');
        $review->assertSee('Towing Fee');
    }

    public function test_clampable_citation_appears_as_eligible_on_clamping_create(): void
    {
        $this->fakeStorage();

        $violationType = ViolationType::create([
            'code' => 'no-parking',
            'name' => 'No Parking',
            'penalty_amount' => 500,
            'is_active' => true,
            'is_clampable' => true,
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/citations', [
                'violation_type_id' => $violationType->id,
                'vehicle_plate' => 'CLP-1234',
                'location' => 'Test Street',
                'evidence' => [UploadedFile::fake()->image('evidence.jpg')],
            ]);

        $response = $this->actingAs($admin)->get('/clamping/create');

        $response->assertOk();
        $response->assertSee('CLP-1234');
        $response->assertSee('Record Clamp');

        // Selecting a citation (server-side, ?citation_id=) shows the review panel
        // with the full details and the next-step record form.
        $citation = \App\Models\Citation::where('vehicle_plate', 'CLP-1234')->firstOrFail();
        $review = $this->actingAs($admin)->get('/clamping/create?citation_id='.$citation->id);

        $review->assertOk();
        $review->assertSee('Selected Citation — Review');
        $review->assertSee('CLP-1234');
        $review->assertSee('Next Step — Record the Clamp');
    }

    public function test_enforcer_cannot_access_clamping_create(): void
    {
        $enforcer = User::factory()->create([
            'email' => 'clamp-enforcer@example.com',
            'password' => bcrypt('password'),
            'role' => 'enforcer',
        ]);

        $response = $this->actingAs($enforcer)->get('/clamping/create');

        $response->assertForbidden();
    }

    public function test_non_impoundable_violation_does_not_create_impounding_record(): void
    {
        $this->fakeStorage();
        $violationType = $this->violationType();

        $this->actingAs($this->admin())
            ->post('/citations', [
                'violation_type_id' => $violationType->id,
                'vehicle_plate' => 'ABC-1234',
                'location' => 'Test Street',
                'evidence' => [UploadedFile::fake()->image('evidence.jpg')],
            ]);

        $this->assertDatabaseCount('citations', 1);
        $this->assertDatabaseCount('impounding_records', 0);
    }

    private function eligibleCitationForClamping(): \App\Models\Citation
    {
        $this->fakeStorage();

        $violationType = ViolationType::create([
            'code' => 'no-parking',
            'name' => 'No Parking',
            'penalty_amount' => 500,
            'is_active' => true,
            'is_clampable' => true,
        ]);

        $this->actingAs($this->admin())
            ->post('/citations', [
                'violation_type_id' => $violationType->id,
                'vehicle_plate' => 'CLP-1234',
                'location' => 'Test Street',
                'evidence' => [UploadedFile::fake()->image('evidence.jpg')],
            ]);

        return \App\Models\Citation::where('vehicle_plate', 'CLP-1234')->firstOrFail();
    }

    private function eligibleCitationForImpounding(): \App\Models\Citation
    {
        $this->fakeStorage();

        $violationType = ViolationType::create([
            'code' => 'unregistered',
            'name' => 'Unregistered Vehicle',
            'penalty_amount' => 10000,
            'is_active' => true,
            'is_impoundable' => true,
        ]);

        $this->actingAs($this->admin())
            ->post('/citations', [
                'violation_type_id' => $violationType->id,
                'vehicle_plate' => 'IMP-1234',
                'location' => 'Test Street',
                'evidence' => [UploadedFile::fake()->image('evidence.jpg')],
            ]);

        return \App\Models\Citation::where('vehicle_plate', 'IMP-1234')->firstOrFail();
    }

    public function test_clamping_requires_a_selected_citation(): void
    {
        $response = $this->actingAs($this->admin())
            ->from('/clamping/create')
            ->post('/clamping', [
                'vehicle_plate' => 'ABC-1234',
            ]);

        $response->assertSessionHasErrors('citation_id');
        $this->assertDatabaseCount('clamping_records', 0);
    }

    public function test_clamping_uses_citation_evidence_photo(): void
    {
        $citation = $this->eligibleCitationForClamping();

        $this->actingAs($this->admin())
            ->post('/clamping', [
                'vehicle_plate' => $citation->vehicle_plate,
                'citation_id' => $citation->id,
                'location' => 'Test Street',
            ]);

        $this->assertDatabaseCount('clamping_records', 1);

        $record = \App\Models\ClampingRecord::firstOrFail();
        $this->assertSame($citation->id, $record->citation_id);
        $this->assertSame($citation->evidence()->first()->file_path, $record->evidence_path);
        $this->assertSame(\App\Enums\CitationStatus::Clamped, $citation->fresh()->status);
    }

    public function test_clamping_rejects_non_clampable_citation(): void
    {
        $this->fakeStorage();
        $violationType = $this->violationType(); // not clampable
        $citation = \App\Models\Citation::create([
            'citation_number' => 'CIT-NC-000001',
            'violation_type_id' => $violationType->id,
            'vehicle_plate' => 'ABC-1234',
            'issued_by' => $this->admin()->id,
            'penalty_amount' => 500,
            'status' => \App\Enums\CitationStatus::Issued,
            'location' => 'Test Street',
            'issued_at' => now(),
            'due_date' => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->admin())
            ->post('/clamping', [
                'vehicle_plate' => 'ABC-1234',
                'citation_id' => $citation->id,
            ]);

        $response->assertSessionHasErrors('citation_id');
        $this->assertDatabaseCount('clamping_records', 0);
    }

    public function test_impounding_requires_a_selected_citation(): void
    {
        $response = $this->actingAs($this->admin())
            ->from('/impounding/create')
            ->post('/impounding', [
                'vehicle_plate' => 'ABC-1234',
            ]);

        $response->assertSessionHasErrors('citation_id');
        $this->assertDatabaseCount('impounding_records', 0);
    }

    public function test_impounding_uses_citation_evidence_photo(): void
    {
        $citation = $this->eligibleCitationForImpounding();

        $this->actingAs($this->admin())
            ->post('/impounding', [
                'vehicle_plate' => $citation->vehicle_plate,
                'citation_id' => $citation->id,
                'location' => 'Test Street',
                'notes' => '',
            ]);

        $this->assertDatabaseCount('impounding_records', 1);

        $record = \App\Models\ImpoundingRecord::firstOrFail();
        $this->assertSame($citation->id, $record->citation_id);
        $this->assertSame($citation->evidence()->first()->file_path, $record->evidence_path);
        $this->assertSame(\App\Enums\CitationStatus::Impounded, $citation->fresh()->status);
    }
}
