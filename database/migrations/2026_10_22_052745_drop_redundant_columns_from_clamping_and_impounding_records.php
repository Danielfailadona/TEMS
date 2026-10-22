<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Drops redundant columns per P7 deduplication:
     * - clamping_records.vehicle_plate (R1: accessor → citation)
     * - impounding_records.vehicle_plate (R2: accessor → citation)
     * - clamping_records.clamping_fee (R3: accessor → payment)
     * - clamping_records.payment_method (R3: accessor → payment)
     * - clamping_records.reference_number (R3: accessor → payment)
     * - clamping_records.paid_at (R3: accessor → payment)
     */
    public function up(): void
    {
        // Drop index on impounding_records.vehicle_plate before dropping column (SQLite requirement)
        Schema::table('impounding_records', function (Blueprint $table) {
            $table->dropIndex(['status', 'vehicle_plate']);
        });

        Schema::table('clamping_records', function (Blueprint $table) {
            $table->dropColumn([
                'vehicle_plate',
                'clamping_fee',
                'payment_method',
                'reference_number',
                'paid_at',
            ]);
        });

        Schema::table('impounding_records', function (Blueprint $table) {
            $table->dropColumn([
                'vehicle_plate',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clamping_records', function (Blueprint $table) {
            $table->string('vehicle_plate')->nullable()->after('notice_number');
            $table->decimal('clamping_fee', 10, 2)->nullable()->after('released_at');
            $table->string('payment_method')->nullable()->after('clamping_fee');
            $table->string('reference_number')->nullable()->after('payment_method');
            $table->timestamp('paid_at')->nullable()->after('reference_number');
        });

        Schema::table('impounding_records', function (Blueprint $table) {
            $table->string('vehicle_plate')->nullable()->after('notice_number');
        });

        // Recreate index on impounding_records.vehicle_plate
        Schema::table('impounding_records', function (Blueprint $table) {
            $table->index(['status', 'vehicle_plate']);
        });
    }
};