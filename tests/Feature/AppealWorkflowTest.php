<?php

namespace Tests\Feature;

use App\Enums\CitationStatus;
use App\Enums\Role;
use App\Models\Citation;
use App\Models\User;
use App\Models\ViolationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppealWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_owner_can_submit_an_appeal_for_their_citation(): void
    {
        $owner = User::create([
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'password' => 'secret123',
            'role' => Role::VehicleOwner->value,
            'is_active' => true,
        ]);

        $violationType = ViolationType::create([
            'code' => 'OVR-001',
            'name' => 'Illegal Parking',
            'penalty_amount' => 2500,
            'is_active' => true,
        ]);

        $citation = Citation::create([
            'citation_number' => 'CIT-1001',
            'violation_type_id' => $violationType->id,
            'vehicle_plate' => 'ABC123',
            'issued_by' => $owner->id,
            'penalty_amount' => 2500,
            'status' => CitationStatus::Issued,
            'issued_at' => now(),
            'due_date' => now()->addDays(15),
        ]);

        $response = $this->actingAs($owner)
            ->post(route('appeals.store'), [
                'citation_id' => $citation->id,
                'reason' => 'Incorrect violation details',
                'description' => 'The citation was issued in error.',
            ]);

        $response->assertRedirect(route('appeals.index'));
        $this->assertDatabaseHas('appeals', [
            'citation_id' => $citation->id,
            'submitted_by' => $owner->id,
            'status' => 'submitted',
        ]);
    }

    public function test_staff_can_review_an_appeal(): void
    {
        $owner = User::create([
            'name' => 'Owner User',
            'email' => 'owner2@example.com',
            'password' => 'secret123',
            'role' => Role::VehicleOwner->value,
            'is_active' => true,
        ]);

        $staff = User::create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'password' => 'secret123',
            'role' => Role::Administrator->value,
            'is_active' => true,
        ]);

        $violationType = ViolationType::create([
            'code' => 'OVR-002',
            'name' => 'Broken Signal',
            'penalty_amount' => 3500,
            'is_active' => true,
        ]);

        $citation = Citation::create([
            'citation_number' => 'CIT-1002',
            'violation_type_id' => $violationType->id,
            'vehicle_plate' => 'XYZ999',
            'issued_by' => $staff->id,
            'penalty_amount' => 3500,
            'status' => CitationStatus::Issued,
            'issued_at' => now(),
            'due_date' => now()->addDays(15),
        ]);

        $appeal = \App\Models\Appeal::create([
            'citation_id' => $citation->id,
            'submitted_by' => $owner->id,
            'reason' => 'Incorrect violation details',
            'description' => 'Please review.',
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($staff)
            ->patch(route('appeals.update', $appeal), [
                'status' => 'approved',
                'decision_notes' => 'Appeal approved after review.',
            ]);

        $response->assertRedirect(route('appeals.show', $appeal));
        $this->assertDatabaseHas('appeals', [
            'id' => $appeal->id,
            'status' => 'approved',
            'reviewed_by' => $staff->id,
        ]);
    }
}
