<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function ($table) {
            $table->bigInteger('cashier_id')->nullable()->change();
            $table->timestamp('paid_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function ($table) {
            $table->bigInteger('cashier_id')->nullable(false)->change();
            $table->timestamp('paid_at')->nullable(false)->change();
        });
    }
};
