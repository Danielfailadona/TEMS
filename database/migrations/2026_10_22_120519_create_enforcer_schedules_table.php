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
        Schema::create('enforcer_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enforcer_id')->constrained('users')->cascadeOnDelete();
            $table->tinyInteger('day_of_week')->unsigned(); // 0=Sunday, 1=Monday, ..., 6=Saturday
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['enforcer_id', 'day_of_week', 'start_time', 'end_time'], 'enforcer_schedule_unique');
            $table->index(['enforcer_id', 'day_of_week', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enforcer_schedules');
    }
};