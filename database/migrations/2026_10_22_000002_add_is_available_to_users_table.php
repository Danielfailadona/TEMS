<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_available')) {
                $table->boolean('is_available')->default(true)->after('is_online');
                $table->index('is_available');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_available')) {
                $table->dropIndex(['is_available']);
                $table->dropColumn('is_available');
            }
        });
    }
};
