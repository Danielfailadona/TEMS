<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('violation_types', function (Blueprint $table) {
            $table->boolean('is_clampable')->default(false)->after('is_impoundable');
        });
    }

    public function down(): void
    {
        Schema::table('violation_types', function (Blueprint $table) {
            $table->dropColumn('is_clampable');
        });
    }
};