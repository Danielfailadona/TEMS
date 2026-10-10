<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('payments', function ($table) {
                $table->dropForeign(['cashier_id']);
            });
        }

        Schema::table('payments', function ($table) {
            $table->unsignedBigInteger('cashier_id')->nullable()->change();
            $table->timestamp('paid_at')->nullable()->change();
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('payments', function ($table) {
                $table->foreign('cashier_id')->references('id')->on('users');
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('payments', function ($table) {
                $table->dropForeign(['cashier_id']);
            });
        }

        Schema::table('payments', function ($table) {
            $table->unsignedBigInteger('cashier_id')->nullable(false)->change();
            $table->timestamp('paid_at')->nullable(false)->change();
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('payments', function ($table) {
                $table->foreign('cashier_id')->references('id')->on('users');
            });
        }
    }
};

