<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('appeals', 'guest_name') && ! Schema::hasColumn('appeals', 'guest_contact')) {
            return;
        }

        Schema::table('appeals', function (Blueprint $table) {
            $table->dropColumn(['guest_name', 'guest_contact']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('appeals', 'guest_name')) {
            Schema::table('appeals', function (Blueprint $table) {
                $table->string('guest_name')->nullable()->after('submitted_by');
            });
        }

        if (! Schema::hasColumn('appeals', 'guest_contact')) {
            Schema::table('appeals', function (Blueprint $table) {
                $table->string('guest_contact')->nullable()->after('guest_name');
            });
        }
    }
};
