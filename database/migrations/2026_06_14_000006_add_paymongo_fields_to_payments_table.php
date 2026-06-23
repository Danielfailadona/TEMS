<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('paymongo_checkout_id')->nullable()->unique()->after('receipt_number');
            $table->string('paymongo_payment_intent_id')->nullable()->after('paymongo_checkout_id');
            $table->string('paymongo_status')->nullable()->after('paymongo_payment_intent_id');
            $table->string('online_payment_method')->nullable()->after('paymongo_status');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['paymongo_checkout_id', 'paymongo_payment_intent_id', 'paymongo_status', 'online_payment_method']);
        });
    }
};
