<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Rename the table.
        if (Schema::hasTable('clamping_requests') && ! Schema::hasTable('citizen_reports')) {
            Schema::rename('clamping_requests', 'citizen_reports');
        }

        // 2. Add the new columns (all nullable so existing rows are untouched).
        Schema::table('citizen_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('citizen_reports', 'report_number')) {
                $table->string('report_number')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('citizen_reports', 'vehicle_make')) {
                $table->string('vehicle_make')->nullable()->after('vehicle_plate');
            }
            if (! Schema::hasColumn('citizen_reports', 'vehicle_model')) {
                $table->string('vehicle_model')->nullable()->after('vehicle_make');
            }
            if (! Schema::hasColumn('citizen_reports', 'vehicle_color')) {
                $table->string('vehicle_color')->nullable()->after('vehicle_model');
            }
            if (! Schema::hasColumn('citizen_reports', 'citation_id')) {
                $table->unsignedBigInteger('citation_id')->nullable()->after('clamping_record_id');
                $table->foreign('citation_id')->references('id')->on('citations')->nullOnDelete();
            }
            if (! Schema::hasColumn('citizen_reports', 'resolution_type')) {
                $table->string('resolution_type')->nullable()->after('rejection_reason');
            }
            if (! Schema::hasColumn('citizen_reports', 'resolution_notes')) {
                $table->text('resolution_notes')->nullable()->after('resolution_type');
            }
            if (! Schema::hasColumn('citizen_reports', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('resolution_notes');
            }
        });

        // 3. Backfill report numbers from the legacy reference_number (deterministic).
        if (Schema::hasColumn('citizen_reports', 'reference_number')) {
            foreach (DB::table('citizen_reports')->whereNull('report_number')->get() as $row) {
                $number = $row->reference_number
                    ?: 'RPT-'.str_pad((string) $row->id, 6, '0', STR_PAD_LEFT);

                DB::table('citizen_reports')->where('id', $row->id)->update(['report_number' => $number]);
            }
        }

        // 4. Drop the superseded columns. Drop the legacy unique index first so
        //    SQLite (tests) can drop the column cleanly.
        Schema::table('citizen_reports', function (Blueprint $table) {
            if (Schema::hasColumn('citizen_reports', 'reference_number')) {
                try {
                    $table->dropUnique('clamping_requests_reference_number_unique');
                } catch (\Throwable $e) {
                    // Index name may differ / already gone — ignore.
                }
                $table->dropColumn('reference_number');
            }
            if (Schema::hasColumn('citizen_reports', 'vehicle_description')) {
                $table->dropColumn('vehicle_description');
            }
        });

        // 5. Widen the status column (drops the old enum check) then migrate the data.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE citizen_reports DROP CONSTRAINT IF EXISTS clamping_requests_status_check');
            DB::statement('ALTER TABLE citizen_reports DROP CONSTRAINT IF EXISTS citizen_reports_status_check');
        }

        Schema::table('citizen_reports', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });

        DB::table('citizen_reports')->where('status', 'approved')->update(['status' => 'accepted']);

        // 6. Keep archive morph types consistent.
        if (Schema::hasTable('archives')) {
            DB::table('archives')
                ->where('archivable_type', 'App\Models\ClampingRequest')
                ->update(['archivable_type' => 'App\Models\CitizenReport']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('archives')) {
            DB::table('archives')
                ->where('archivable_type', 'App\Models\CitizenReport')
                ->update(['archivable_type' => 'App\Models\ClampingRequest']);
        }

        DB::table('citizen_reports')->where('status', 'accepted')->update(['status' => 'approved']);

        Schema::table('citizen_reports', function (Blueprint $table) {
            foreach (['report_number', 'vehicle_make', 'vehicle_model', 'vehicle_color', 'citation_id', 'resolution_type', 'resolution_notes', 'resolved_at'] as $column) {
                if (Schema::hasColumn('citizen_reports', $column)) {
                    if ($column === 'citation_id') {
                        $table->dropForeign(['citation_id']);
                    }
                    $table->dropColumn($column);
                }
            }
            if (! Schema::hasColumn('citizen_reports', 'vehicle_description')) {
                $table->text('vehicle_description')->nullable();
            }
            if (! Schema::hasColumn('citizen_reports', 'reference_number')) {
                $table->string('reference_number')->nullable();
            }
        });

        if (Schema::hasTable('citizen_reports') && ! Schema::hasTable('clamping_requests')) {
            Schema::rename('citizen_reports', 'clamping_requests');
        }
    }
};
