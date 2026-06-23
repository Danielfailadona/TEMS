<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citations', function (Blueprint $table) {
            $table->id();
            $table->string('citation_number')->unique();
            $table->foreignId('violation_type_id')->constrained();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('issued_by')->constrained('users');
            $table->decimal('penalty_amount', 10, 2);
            $table->string('status')->default('issued');
            $table->text('location')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('issued_at');
            $table->date('due_date');
            $table->timestamps();

            $table->index(['status', 'vehicle_id']);
            $table->index('citation_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citations');
    }
};
