<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citations', function (Blueprint $table) {
            $table->string('vehicle_plate')->after('vehicle_id');
            $table->string('vehicle_make')->nullable()->after('vehicle_plate');
            $table->string('vehicle_model')->nullable()->after('vehicle_make');
            $table->string('vehicle_type')->nullable()->after('vehicle_model');
            $table->string('vehicle_color')->nullable()->after('vehicle_type');
            $table->string('driver_name')->nullable()->after('vehicle_color');
            $table->string('driver_license')->nullable()->after('driver_name');
        });

        Schema::table('clamping_records', function (Blueprint $table) {
            $table->string('vehicle_plate')->after('vehicle_id');
        });
    }

    public function down(): void
    {
        Schema::table('citations', function (Blueprint $table) {
            $table->dropColumn(['vehicle_plate', 'vehicle_make', 'vehicle_model', 'vehicle_type', 'vehicle_color', 'driver_name', 'driver_license']);
        });

        Schema::table('clamping_records', function (Blueprint $table) {
            $table->dropColumn('vehicle_plate');
        });
    }
};
