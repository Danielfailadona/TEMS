<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('citations', 'custom_violation_name')) {
            return;
        }

        Schema::table('citations', function (Blueprint $table) {
            $table->string('custom_violation_name')->nullable()->after('violation_type_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('citations', 'custom_violation_name')) {
            return;
        }

        Schema::table('citations', function (Blueprint $table) {
            $table->dropColumn('custom_violation_name');
        });
    }
};
