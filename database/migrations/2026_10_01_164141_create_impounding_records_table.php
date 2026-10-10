<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('impounding_records', function (Blueprint $table) {
            $table->id();
            $table->string('notice_number')->unique();
            $table->string('vehicle_plate');
            $table->foreignId('citation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('clamping_record_id')->nullable()->constrained('clamping_records')->nullOnDelete();
            $table->foreignId('impounded_by')->constrained('users');
            $table->string('status')->default('impounded');
            $table->text('location')->nullable();
            $table->text('notes')->nullable();
            $table->string('evidence_path')->nullable();
            $table->decimal('towing_fee', 10, 2)->default(0);
            $table->decimal('storage_fee_per_day', 10, 2)->default(0);
            $table->decimal('admin_fee', 10, 2)->default(0);
            $table->timestamp('grace_until')->nullable();
            $table->timestamp('impounded_at')->useCurrent();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'vehicle_plate']);
            $table->index('impounded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('impounding_records');
    }
};
