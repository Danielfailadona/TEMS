<?php

namespace App\Console\Commands;

use App\Models\Archive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PruneStaleData extends Command
{
    protected $signature = 'tems:prune
        {--days=180 : Retain rows newer than this many days}
        {--compact-archives : Null out legacy archive snapshots whose source record still exists (reclaims storage)}';

    protected $description = 'Prune stale sessions, activity logs, failed jobs and read notifications, and optionally compact archive snapshots.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $this->info("Pruning rows older than {$days} days (before {$cutoff->toDateTimeString()})...");

        if (Schema::hasTable('activity_log')) {
            $count = DB::table('activity_log')->where('created_at', '<', $cutoff)->delete();
            $this->line("  activity_log: {$count} deleted");
        }

        if (Schema::hasTable('sessions')) {
            $count = DB::table('sessions')->where('last_activity', '<', $cutoff->timestamp)->delete();
            $this->line("  sessions: {$count} deleted");
        }

        if (Schema::hasTable('failed_jobs')) {
            $count = DB::table('failed_jobs')->where('failed_at', '<', $cutoff)->delete();
            $this->line("  failed_jobs: {$count} deleted");
        }

        if (Schema::hasTable('notifications')) {
            $count = DB::table('notifications')
                ->whereNotNull('read_at')
                ->where('created_at', '<', $cutoff)
                ->delete();
            $this->line("  notifications (read): {$count} deleted");
        }

        if ($this->option('compact-archives')) {
            $this->compactArchives();
        }

        $this->info('Prune complete.');

        return self::SUCCESS;
    }

    /**
     * Legacy archive rows stored a full JSON copy of the record. Now that
     * archive details are fetched live, those copies can be dropped wherever
     * the source record still exists.
     */
    protected function compactArchives(): void
    {
        $compacted = 0;

        Archive::query()
            ->whereNotNull('snapshot')
            ->select(['id', 'archivable_type', 'archivable_id'])
            ->chunkById(500, function ($archives) use (&$compacted) {
                foreach ($archives as $archive) {
                    $modelClass = $archive->archivable_type;

                    if (! is_string($modelClass) || ! class_exists($modelClass)) {
                        continue;
                    }

                    if ($modelClass::whereKey($archive->archivable_id)->exists()) {
                        Archive::whereKey($archive->id)->update(['snapshot' => null]);
                        $compacted++;
                    }
                }
            });

        $this->line("  archives compacted: {$compacted}");
    }
}
