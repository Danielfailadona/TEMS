<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citations', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
            $table->dropForeign(['driver_id']);
            $table->dropColumn(['vehicle_id', 'driver_id']);
        });

        Schema::table('clamping_records', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
            $table->dropColumn('vehicle_id');
        });

        Schema::dropIfExists('vehicle_releases');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('drivers');
    }

    public function down(): void
    {
        Schema::create('drivers', function ($table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('license_number')->unique();
            $table->date('license_expiry')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('vehicles', function ($table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users');
            $table->foreignId('driver_id')->nullable()->constrained();
            $table->string('plate_number')->unique();
            $table->string('classification')->nullable();
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->string('color')->nullable();
            $table->year('year')->nullable();
            $table->string('registration_status')->default('active');
            $table->timestamps();
        });

        Schema::create('vehicle_releases', function ($table) {
            $table->id();
            $table->string('release_number');
            $table->foreignId('clamping_record_id')->constrained('clamping_records');
            $table->foreignId('released_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamp('released_at');
            $table->timestamps();
        });
    }
};
