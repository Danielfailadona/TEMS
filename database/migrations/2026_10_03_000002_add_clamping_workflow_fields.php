<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clamping_records', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('status');
            $table->timestamp('released_at')->nullable()->after('paid_at');
            $table->decimal('clamping_fee', 8, 2)->nullable()->after('released_at');
            $table->string('payment_method')->nullable()->after('clamping_fee');
            $table->string('reference_number')->nullable()->after('payment_method');
        });

        Schema::table('vehicle_releases', function (Blueprint $table) {
            $table->foreignId('impounding_record_id')
                ->nullable()
                ->after('clamping_record_id')
                ->constrained('impounding_records')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_releases', function (Blueprint $table) {
            $table->dropForeign(['impounding_record_id']);
            $table->dropColumn('impounding_record_id');
        });

        Schema::table('clamping_records', function (Blueprint $table) {
            $table->dropColumn(['paid_at', 'released_at', 'clamping_fee', 'payment_method', 'reference_number']);
        });
    }
};