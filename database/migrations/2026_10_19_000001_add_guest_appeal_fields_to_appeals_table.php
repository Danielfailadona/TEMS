<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('appeals', function ($table) {
                $table->dropForeign(['submitted_by']);
            });
        }

        Schema::table('appeals', function ($table) {
            $table->unsignedBigInteger('submitted_by')->nullable()->change();
            $table->string('guest_name')->nullable()->after('submitted_by');
            $table->string('guest_contact')->nullable()->after('guest_name');
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('appeals', function ($table) {
                $table->foreign('submitted_by')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('appeals', function ($table) {
            $table->dropColumn(['guest_name', 'guest_contact']);
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('appeals', function ($table) {
                $table->dropForeign(['submitted_by']);
            });
        }

        Schema::table('appeals', function ($table) {
            $table->unsignedBigInteger('submitted_by')->nullable(false)->change();
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('appeals', function ($table) {
                $table->foreign('submitted_by')->references('id')->on('users');
            });
        }
    }
};
