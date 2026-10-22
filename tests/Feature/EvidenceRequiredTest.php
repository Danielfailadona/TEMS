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
            'email' => 'evidence-admin@example.com',
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

    public function test_impoundable_violation_creates_linked_impounding_record(): void
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
                'impounding_notes' => 'Parked on sidewalk',
                'evidence' => [UploadedFile::fake()->image('evidence.jpg')],
            ]);

        $this->assertDatabaseCount('citations', 1);
        $this->assertDatabaseCount('impounding_records', 1);

        $citation = \App\Models\Citation::firstOrFail();
        $impounding = \App\Models\ImpoundingRecord::firstOrFail();

        $this->assertSame(\App\Enums\CitationStatus::Impounded, $citation->status);
        $this->assertSame($citation->id, $impounding->citation_id);
        $this->assertSame('Parked on sidewalk', $impounding->notes);
        $this->assertSame($citation->evidence()->first()->file_path, $impounding->evidence_path);
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

    public function test_clamping_requires_evidence_photo(): void
    {
        $response = $this->actingAs($this->admin())
            ->from('/clamping/create')
            ->post('/clamping', [
                'vehicle_plate' => 'ABC-1234',
            ]);

        $response->assertSessionHasErrors('evidence');
        $this->assertDatabaseCount('clamping_records', 0);
    }

    public function test_clamping_is_created_with_evidence_photo(): void
    {
        $this->fakeStorage();

        $this->actingAs($this->admin())
            ->post('/clamping', [
                'vehicle_plate' => 'ABC-1234',
                'location' => 'Test Street',
                'evidence' => UploadedFile::fake()->image('clamp.jpg'),
            ]);

        $this->assertDatabaseCount('clamping_records', 1);
    }

    public function test_impounding_requires_evidence_photo(): void    {
        $response = $this->actingAs($this->admin())
            ->from('/impounding/create')
            ->post('/impounding', [
                'vehicle_plate' => 'ABC-1234',
            ]);

        $response->assertSessionHasErrors('evidence');
        $this->assertDatabaseCount('impounding_records', 0);
    }

    public function test_impounding_is_created_with_evidence_photo(): void
    {
        $this->fakeStorage();

        $response = $this->actingAs($this->admin())
            ->post('/impounding', [
                'vehicle_plate' => 'ABC-1234',
                'location' => 'Test Street',
                'notes' => '',
                'evidence' => UploadedFile::fake()->image('impound.jpg'),
            ]);

        $this->assertDatabaseCount('impounding_records', 1);
    }
}
