<?php

namespace Tests\Feature;

use App\Models\EnforcerSchedule;
use App\Models\User;
use App\Services\AutoAssignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EnforcerScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'email' => 'sched-admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'administrator',
            'account_status' => 'approved',
            'is_active' => true,
        ]);
    }

    private function enforcer(): User
    {
        return User::factory()->create([
            'email' => 'sched-enforcer@example.com',
            'password' => bcrypt('password'),
            'role' => 'enforcer',
            'account_status' => 'approved',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_save_schedule_and_it_persists_days_and_active_flags(): void
    {
        $enforcer = $this->enforcer();

        $this->actingAs($this->admin())
            ->post(route('operations.task-management.schedule.update', $enforcer), [
                'start_time' => '06:00',
                'end_time' => '12:00',
                'days' => [3, 4, 5, 6],
                'is_active_days' => [3 => 1, 4 => 1, 5 => 1, 6 => 1],
            ])
            ->assertRedirect();

        $schedule = EnforcerSchedule::where('enforcer_id', $enforcer->id)->first();
        $this->assertNotNull($schedule);
        $this->assertSame([3, 4, 5, 6], $schedule->days);
        $this->assertTrue($schedule->isDayActive(5));
        $this->assertFalse($schedule->isDayActive(0));
        $this->assertSame('06:00', $schedule->start_time_short);
        $this->assertSame('12:00', $schedule->end_time_short);
    }

    public function test_schedule_is_within_schedule_on_active_day_within_hours(): void
    {
        $enforcer = $this->enforcer();

        EnforcerSchedule::create([
            'enforcer_id' => $enforcer->id,
            'days' => [3, 4, 5, 6],
            'start_time' => '06:00',
            'end_time' => '12:00',
            'is_active_days' => [3 => true, 4 => true, 5 => true, 6 => true],
        ]);

        $friday = Carbon::parse('2026-10-23 08:00:00');
        $this->assertTrue(EnforcerSchedule::isEnforcerScheduledAt($enforcer->id, $friday));

        $this->travelTo($friday);
        $this->assertTrue($enforcer->fresh()->isWithinSchedule());

        $outOfHours = Carbon::parse('2026-10-23 13:00:00');
        $this->assertFalse(EnforcerSchedule::isEnforcerScheduledAt($enforcer->id, $outOfHours));

        $inactiveDay = Carbon::parse('2026-10-19 08:00:00');
        $this->assertFalse(EnforcerSchedule::isEnforcerScheduledAt($enforcer->id, $inactiveDay));
    }

    public function test_auto_assign_service_detects_enforcer_within_schedule(): void
    {
        $enforcer = $this->enforcer();

        EnforcerSchedule::create([
            'enforcer_id' => $enforcer->id,
            'days' => [5],
            'start_time' => '06:00',
            'end_time' => '12:00',
            'is_active_days' => [5 => true],
        ]);

        $this->travelTo(Carbon::parse('2026-10-23 08:00:00'));

        $this->assertTrue(AutoAssignService::isEnforcerCurrentlyScheduled($enforcer->fresh()));

        $this->travelTo(Carbon::parse('2026-10-23 13:00:00'));
        $this->assertFalse(AutoAssignService::isEnforcerCurrentlyScheduled($enforcer->fresh()));
    }

    public function test_saving_schedule_requires_at_least_one_day(): void
    {
        $enforcer = $this->enforcer();

        $this->actingAs($this->admin())
            ->post(route('operations.task-management.schedule.update', $enforcer), [
                'start_time' => '06:00',
                'end_time' => '12:00',
                'days' => [],
            ])
            ->assertSessionHasErrors('days');

        $this->assertSame(0, EnforcerSchedule::where('enforcer_id', $enforcer->id)->count());
    }
}
