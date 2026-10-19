<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appeals', function (Blueprint $table) {
            $table->decimal('original_amount', 10, 2)->nullable()->after('decision_notes');
            $table->decimal('adjusted_amount', 10, 2)->nullable()->after('original_amount');
        });
    }

    public function down(): void
    {
        Schema::table('appeals', function (Blueprint $table) {
            $table->dropColumn(['original_amount', 'adjusted_amount']);
        });
    }
};
