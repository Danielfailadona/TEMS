<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing zone.team_id values to team_zone pivot table
        $zones = DB::table('zones')->whereNotNull('team_id')->get();
        foreach ($zones as $zone) {
            DB::table('team_zone')->insert([
                'team_id' => $zone->team_id,
                'zone_id' => $zone->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse: populate zones.team_id from team_zone (first match)
        $teamZones = DB::table('team_zone')->get();
        foreach ($teamZones as $tz) {
            DB::table('zones')
                ->where('id', $tz->zone_id)
                ->update(['team_id' => $tz->team_id]);
        }
    }
};
