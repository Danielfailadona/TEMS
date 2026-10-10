<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unique('citation_id');
        });

        Schema::table('clamping_records', function (Blueprint $table) {
            $table->unique('citation_id');
        });

        Schema::table('appeals', function (Blueprint $table) {
            $table->unique(['citation_id', 'submitted_by']);
        });

        if (! Schema::hasIndex('citations', 'citations_vehicle_plate_index')) {
            Schema::table('citations', function (Blueprint $table) {
                $table->index('vehicle_plate');
            });
        }

        if (! Schema::hasIndex('citations', 'citations_due_date_index')) {
            Schema::table('citations', function (Blueprint $table) {
                $table->index('due_date');
            });
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_citation_id_unique');
        });

        Schema::table('clamping_records', function (Blueprint $table) {
            $table->dropUnique('clamping_records_citation_id_unique');
        });

        Schema::table('appeals', function (Blueprint $table) {
            $table->dropUnique('appeals_citation_submitter_unique');
        });

        Schema::table('citations', function (Blueprint $table) {
            $table->dropIndex('citations_vehicle_plate_index');
            $table->dropIndex('citations_due_date_index');
        });
    }
};