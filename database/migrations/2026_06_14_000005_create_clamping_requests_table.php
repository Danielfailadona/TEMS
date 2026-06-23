<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clamping_requests', function (Blueprint $table) {
            $table->id();
            $table->string('requester_name');
            $table->string('requester_phone');
            $table->string('requester_email');
            $table->text('location_address');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('vehicle_plate');
            $table->text('vehicle_description')->nullable();
            $table->string('evidence_photo');
            $table->text('additional_notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'resolved'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->unsignedBigInteger('clamping_record_id')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('vehicle_plate');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clamping_requests');
    }
};
