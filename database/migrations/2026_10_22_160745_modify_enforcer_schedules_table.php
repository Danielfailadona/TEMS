<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new columns first
        Schema::table('enforcer_schedules', function (Blueprint $table) {
            $table->json('days')->nullable()->after('enforcer_id'); // e.g., [1,2,3,4,5]
            $table->json('is_active_days')->nullable()->after('days'); // e.g., {1:true, 2:true, 3:true, 4:true, 5:true}
        });

        // Migrate existing data - consolidate per-day rows into single schedule per enforcer
        $this->consolidateSchedules();

        // Drop old columns and indexes
        Schema::table('enforcer_schedules', function (Blueprint $table) {
            $table->dropUnique('enforcer_schedule_unique');
            $table->dropIndex(['enforcer_id', 'day_of_week', 'is_active']);
            $table->dropColumn(['day_of_week', 'is_active']);
        });

        // Add new unique index for single schedule per enforcer
        Schema::table('enforcer_schedules', function (Blueprint $table) {
            $table->unique(['enforcer_id', 'start_time', 'end_time'], 'enforcer_schedule_unique');
        });
    }

    /**
     * Consolidate existing per-day schedules into single schedule per enforcer
     * with JSON days array and is_active_days object.
     */
    private function consolidateSchedules(): void
    {
        $schedules = \App\Models\EnforcerSchedule::with('enforcer')->get()
            ->groupBy('enforcer_id')
            ->map(function ($group) {
                // Group by start_time + end_time
                return $group->groupBy(function ($item) {
                    return $item->start_time . '|' . $item->end_time;
                })->map(function ($timeGroup) {
                    // Sort by day_of_week
                    $timeGroup = $timeGroup->sortBy('day_of_week');
                    
                    $days = [];
                    $isActiveDays = [];
                    
                    foreach ($timeGroup as $schedule) {
                        $day = (int) $schedule->day_of_week;
                        $days[] = $day;
                        $isActiveDays[$day] = (bool) $schedule->is_active;
                    }
                    
                    // Sort days
                    sort($days);
                    
                    return [
                        'start_time' => $timeGroup->first()->start_time,
                        'end_time' => $timeGroup->first()->end_time,
                        'days' => $days,
                        'is_active_days' => $isActiveDays,
                    ];
                })->values()->first(); // Take first time group (or first by some logic)
            })->filter();

        foreach ($schedules as $enforcerId => $consolidated) {
            if (!$consolidated) continue;
            
            // Delete old rows for this enforcer
            \App\Models\EnforcerSchedule::where('enforcer_id', $enforcerId)->delete();
            
            // Create new consolidated schedule
            \App\Models\EnforcerSchedule::create([
                'enforcer_id' => $enforcerId,
                'days' => $consolidated['days'],
                'start_time' => $consolidated['start_time'],
                'end_time' => $consolidated['end_time'],
                'is_active_days' => $consolidated['is_active_days'],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore old columns
        Schema::table('enforcer_schedules', function (Blueprint $table) {
            $table->tinyInteger('day_of_week')->unsigned()->after('enforcer_id');
            $table->boolean('is_active')->default(true)->after('end_time');
        });

        // We cannot easily restore the per-day rows from consolidated data
        // This is a one-way migration for the most part
        Schema::table('enforcer_schedules', function (Blueprint $table) {
            $table->dropUnique('enforcer_schedule_unique');
            $table->dropColumn(['days', 'is_active_days']);
        });
        
        Schema::table('enforcer_schedules', function (Blueprint $table) {
            $table->unique(['enforcer_id', 'day_of_week', 'start_time', 'end_time'], 'enforcer_schedule_unique');
            $table->index(['enforcer_id', 'day_of_week', 'is_active']);
        });
    }
};