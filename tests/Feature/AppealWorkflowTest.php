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

    public function test_front_desk_can_file_an_appeal_for_a_citation(): void
    {
        $frontDesk = User::create([
            'name' => 'Front Desk User',
            'email' => 'frontdesk@example.com',
            'password' => 'secret123',
            'role' => Role::FrontDesk->value,
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
            'issued_by' => $frontDesk->id,
            'penalty_amount' => 2500,
            'status' => CitationStatus::Issued,
            'issued_at' => now(),
            'due_date' => now()->addDays(15),
        ]);

        $response = $this->actingAs($frontDesk)
            ->post(route('appeals.store'), [
                'citation_id' => $citation->id,
                'reason' => 'Incorrect violation details',
                'description' => 'The citation was issued in error.',
            ]);

        $response->assertRedirect(route('appeals.index'));
        $this->assertDatabaseHas('appeals', [
            'citation_id' => $citation->id,
            'submitted_by' => $frontDesk->id,
            'status' => 'submitted',
        ]);
    }

    public function test_vehicle_owner_cannot_file_an_appeal(): void
    {
        $owner = User::create([
            'name' => 'Owner User',
            'email' => 'owner-cannot@example.com',
            'password' => 'secret123',
            'role' => Role::VehicleOwner->value,
            'is_active' => true,
        ]);

        $violationType = ViolationType::create([
            'code' => 'OVR-NOAPL',
            'name' => 'No Appeal',
            'penalty_amount' => 500,
            'is_active' => true,
        ]);

        $citation = Citation::create([
            'citation_number' => 'CIT-NOAPL',
            'violation_type_id' => $violationType->id,
            'vehicle_plate' => 'NOAPL1',
            'issued_by' => $owner->id,
            'penalty_amount' => 500,
            'status' => CitationStatus::Issued,
            'issued_at' => now(),
            'due_date' => now()->addDays(15),
        ]);

        $this->actingAs($owner)
            ->post(route('appeals.store'), [
                'citation_id' => $citation->id,
                'reason' => 'Nope',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('appeals', 0);
    }

    public function test_appeal_exposes_a_ticket_number(): void
    {
        $appeal = new \App\Models\Appeal();
        $appeal->id = 42;

        $this->assertSame('APL-000042', $appeal->ticketNumber());
    }

    public function test_guest_appeal_columns_are_removed(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('appeals', 'guest_name'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('appeals', 'guest_contact'));
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

    public function test_approving_appeal_applies_default_discount_to_citation(): void
    {
        $owner = User::create([
            'name' => 'Owner User',
            'email' => 'owner3@example.com',
            'password' => 'secret123',
            'role' => Role::VehicleOwner->value,
            'is_active' => true,
        ]);

        $staff = User::create([
            'name' => 'Staff User',
            'email' => 'staff3@example.com',
            'password' => 'secret123',
            'role' => Role::Administrator->value,
            'is_active' => true,
        ]);

        $violationType = ViolationType::create([
            'code' => 'OVR-003',
            'name' => 'Reckless Driving',
            'penalty_amount' => 2000,
            'is_active' => true,
        ]);

        $citation = Citation::create([
            'citation_number' => 'CIT-1003',
            'violation_type_id' => $violationType->id,
            'vehicle_plate' => 'DSC111',
            'issued_by' => $staff->id,
            'penalty_amount' => 2000,
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

        $this->actingAs($staff)
            ->patch(route('appeals.update', $appeal), [
                'status' => 'approved',
                'decision_notes' => 'Approved.',
            ]);

        $this->assertEquals(2000.0, (float) $appeal->fresh()->original_amount);
        $this->assertEquals(1700.0, (float) $appeal->fresh()->adjusted_amount);
        $this->assertEquals(1700.0, (float) $citation->fresh()->penalty_amount);
    }

    public function test_approving_appeal_uses_explicit_adjusted_amount(): void
    {
        $owner = User::create([
            'name' => 'Owner User',
            'email' => 'owner4@example.com',
            'password' => 'secret123',
            'role' => Role::VehicleOwner->value,
            'is_active' => true,
        ]);

        $staff = User::create([
            'name' => 'Staff User',
            'email' => 'staff4@example.com',
            'password' => 'secret123',
            'role' => Role::Administrator->value,
            'is_active' => true,
        ]);

        $violationType = ViolationType::create([
            'code' => 'OVR-004',
            'name' => 'Overspeeding',
            'penalty_amount' => 1000,
            'is_active' => true,
        ]);

        $citation = Citation::create([
            'citation_number' => 'CIT-1004',
            'violation_type_id' => $violationType->id,
            'vehicle_plate' => 'ADJ222',
            'issued_by' => $staff->id,
            'penalty_amount' => 1000,
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

        $this->actingAs($staff)
            ->patch(route('appeals.update', $appeal), [
                'status' => 'approved',
                'adjusted_amount' => 250,
                'decision_notes' => 'Partial waiver granted.',
            ]);

        $this->assertEquals(1000.0, (float) $appeal->fresh()->original_amount);
        $this->assertEquals(250.0, (float) $appeal->fresh()->adjusted_amount);
        $this->assertEquals(250.0, (float) $citation->fresh()->penalty_amount);
    }
}
