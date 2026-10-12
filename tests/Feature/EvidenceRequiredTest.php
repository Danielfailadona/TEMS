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

    public function test_impounding_requires_evidence_photo(): void
    {
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
