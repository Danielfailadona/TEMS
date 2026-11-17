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
        Schema::create('team_zone', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->nullOnDelete();
            $table->foreignId('zone_id')->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['team_id', 'zone_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_zone');
    }
};
