<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_releases', function (Blueprint $table) {
            $table->id();
            $table->string('release_number')->unique();
            $table->foreignId('clamping_record_id')->constrained();
            $table->foreignId('released_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamp('released_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_releases');
    }
};
