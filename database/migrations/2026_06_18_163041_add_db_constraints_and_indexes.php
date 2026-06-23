<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_citation_id_unique UNIQUE (citation_id)');

        DB::statement('ALTER TABLE clamping_records ADD CONSTRAINT clamping_records_citation_id_unique UNIQUE (citation_id)');

        DB::statement('ALTER TABLE appeals ADD CONSTRAINT appeals_citation_submitter_unique UNIQUE (citation_id, submitted_by)');

        if (! $this->indexExists('citations', 'citations_vehicle_plate_index')) {
            DB::statement('CREATE INDEX citations_vehicle_plate_index ON citations (vehicle_plate)');
        }

        if (! $this->indexExists('citations', 'citations_due_date_index')) {
            DB::statement('CREATE INDEX citations_due_date_index ON citations (due_date)');
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_citation_id_unique');
        DB::statement('ALTER TABLE clamping_records DROP CONSTRAINT IF EXISTS clamping_records_citation_id_unique');
        DB::statement('ALTER TABLE appeals DROP CONSTRAINT IF EXISTS appeals_citation_submitter_unique');
        DB::statement('DROP INDEX IF EXISTS citations_vehicle_plate_index');
        DB::statement('DROP INDEX IF EXISTS citations_due_date_index');
    }

    protected function indexExists(string $table, string $indexName): bool
    {
        return (bool) DB::selectOne(
            "SELECT 1 FROM pg_indexes WHERE tablename = ? AND indexname = ?",
            [$table, $indexName]
        );
    }
};
