<?php

namespace Tests\Feature;

use App\Models\EnforcerSchedule;
use App\Models\SystemNotification;
use App\Models\User;
use App\Services\EnforcerDutyNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EnforcerDutyStatusTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'administrator'): User
    {
        return User::factory()->create([
            'email' => "{$role}-duty@example.com",
            'password' => bcrypt('password'),
            'role' => $role,
            'account_status' => 'approved',
            'is_active' => true,
        ]);
    }

    private function enforcer(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'duty-enforcer@example.com',
            'password' => bcrypt('password'),
            'role' => 'enforcer',
            'account_status' => 'approved',
            'is_active' => true,
        ], $attributes));
    }

    private function scheduleFor(User $enforcer, int $day, string $start = '06:00', string $end = '12:00'): EnforcerSchedule
    {
        return EnforcerSchedule::create([
            'enforcer_id' => $enforcer->id,
            'days' => [$day],
            'start_time' => $start,
            'end_time' => $end,
            'is_active_days' => [$day => true],
        ]);
    }

    private function atFridayWithinShift(): Carbon
    {
        return Carbon::parse('2026-10-23 08:00:00');
    }

    public function test_duty_state_online_when_within_schedule_and_online(): void
    {
        $enforcer = $this->enforcer(['is_online' => true]);
        $this->scheduleFor($enforcer, 5);

        $this->travelTo($this->atFridayWithinShift());

        $this->assertSame('on_duty_online', $enforcer->fresh()->dutyState());
        $this->assertSame('On Duty', $enforcer->fresh()->duty_state_label);
        $this->assertSame('bg-success-soft text-success', $enforcer->fresh()->duty_state_class);
    }

    public function test_duty_state_offline_when_within_schedule_but_not_online(): void
    {
        $enforcer = $this->enforcer(['is_online' => false]);
        $this->scheduleFor($enforcer, 5);

        $this->travelTo($this->atFridayWithinShift());

        $this->assertSame('on_duty_offline', $enforcer->fresh()->dutyState());
        $this->assertSame('On Duty (Offline)', $enforcer->fresh()->duty_state_label);
        $this->assertSame('bg-warning-soft text-warning', $enforcer->fresh()->duty_state_class);
    }

    public function test_duty_state_off_duty_when_outside_schedule(): void
    {
        $enforcer = $this->enforcer(['is_online' => true]);
        $this->scheduleFor($enforcer, 5);

        $this->travelTo(Carbon::parse('2026-10-23 13:00:00'));

        $this->assertSame('off_duty', $enforcer->fresh()->dutyState());
        $this->assertSame('Off Duty', $enforcer->fresh()->duty_state_label);
        $this->assertSame('bg-danger-soft text-danger', $enforcer->fresh()->duty_state_class);
    }

    public function test_no_schedule_uses_muted_label(): void
    {
        $enforcer = $this->enforcer(['is_online' => true]);

        $this->assertFalse($enforcer->hasSchedule());
        $this->assertSame('off_duty', $enforcer->dutyState());
        $this->assertSame('No Schedule', $enforcer->duty_state_label);
        $this->assertSame('bg-gray-soft text-gray', $enforcer->duty_state_class);
    }

    public function test_notifier_skips_first_observation_but_records_state(): void
    {
        $admin = $this->admin();
        $enforcer = $this->enforcer(['is_online' => true]);

        $this->scheduleFor($enforcer, 5);
        $this->travelTo($this->atFridayWithinShift());

        EnforcerDutyNotifier::sync($enforcer->fresh());

        $this->assertSame('on_duty_online', $enforcer->fresh()->last_duty_state);
        $this->assertSame(0, SystemNotification::where('user_id', $admin->id)->count());
    }

    public function test_notifier_alerts_admins_on_transition_to_on_duty_online(): void
    {
        $admin = $this->admin();
        $superAdmin = $this->admin('super_admin');
        $enforcer = $this->enforcer(['is_online' => true, 'last_duty_state' => 'off_duty']);

        $this->scheduleFor($enforcer, 5);
        $this->travelTo($this->atFridayWithinShift());

        EnforcerDutyNotifier::sync($enforcer->fresh());

        $this->assertSame(1, SystemNotification::where('user_id', $admin->id)->where('type', 'enforcer_on_duty_online')->count());
        $this->assertSame(1, SystemNotification::where('user_id', $superAdmin->id)->where('type', 'enforcer_on_duty_online')->count());
        $this->assertSame('on_duty_online', $enforcer->fresh()->last_duty_state);
    }

    public function test_notifier_alerts_admins_when_on_duty_but_offline(): void
    {
        $admin = $this->admin();
        $enforcer = $this->enforcer(['is_online' => false, 'last_duty_state' => 'off_duty']);

        $this->scheduleFor($enforcer, 5);
        $this->travelTo($this->atFridayWithinShift());

        EnforcerDutyNotifier::sync($enforcer->fresh());

        $this->assertSame(1, SystemNotification::where('user_id', $admin->id)->where('type', 'enforcer_on_duty_offline')->count());
        $this->assertSame('on_duty_offline', $enforcer->fresh()->last_duty_state);
    }

    public function test_notifier_deduplicates_repeated_syncs(): void
    {
        $admin = $this->admin();
        $enforcer = $this->enforcer(['is_online' => true, 'last_duty_state' => 'off_duty']);

        $this->scheduleFor($enforcer, 5);
        $this->travelTo($this->atFridayWithinShift());

        EnforcerDutyNotifier::sync($enforcer->fresh());
        EnforcerDutyNotifier::sync($enforcer->fresh());
        EnforcerDutyNotifier::sync($enforcer->fresh());

        $this->assertSame(1, SystemNotification::where('user_id', $admin->id)->count());
    }

    public function test_notifier_does_not_alert_when_going_off_duty(): void
    {
        $admin = $this->admin();
        $enforcer = $this->enforcer(['is_online' => true, 'last_duty_state' => 'on_duty_online']);

        $this->scheduleFor($enforcer, 5);
        $this->travelTo(Carbon::parse('2026-10-23 13:00:00'));

        EnforcerDutyNotifier::sync($enforcer->fresh());

        $this->assertSame(0, SystemNotification::where('user_id', $admin->id)->count());
        $this->assertSame('off_duty', $enforcer->fresh()->last_duty_state);
    }

    public function test_location_ping_marks_enforcer_online_and_notifies_admins(): void
    {
        $admin = $this->admin();
        $enforcer = $this->enforcer(['is_online' => false, 'last_duty_state' => 'off_duty']);

        $this->scheduleFor($enforcer, 5);
        $this->travelTo($this->atFridayWithinShift());

        $this->actingAs($enforcer)->post(route('location.update'), [
            'latitude' => 14.5995,
            'longitude' => 120.9842,
            'accuracy_m' => 5,
        ])->assertSuccessful();

        $this->assertTrue($enforcer->fresh()->is_online);
        $this->assertSame('on_duty_online', $enforcer->fresh()->last_duty_state);
        $this->assertSame(1, SystemNotification::where('user_id', $admin->id)->where('type', 'enforcer_on_duty_online')->count());
    }

    public function test_enforcer_profile_shows_my_schedule_card(): void
    {
        $enforcer = $this->enforcer();
        $this->scheduleFor($enforcer, now()->dayOfWeek, '07:30', '16:30');

        $response = $this->actingAs($enforcer)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertSee('My Schedule');
        $response->assertSee('07:30');
        $response->assertSee('16:30');
    }

    public function test_non_enforcer_profile_does_not_show_schedule_card(): void
    {
        $cashier = User::factory()->create([
            'email' => 'duty-cashier@example.com',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'account_status' => 'approved',
            'is_active' => true,
        ]);

        $response = $this->actingAs($cashier)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertDontSee('My Schedule');
    }
}
