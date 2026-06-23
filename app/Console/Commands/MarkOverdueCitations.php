<?php

namespace App\Console\Commands;

use App\Services\CitationNumberService;
use Illuminate\Console\Command;

class MarkOverdueCitations extends Command
{
    protected $signature = 'citations:mark-overdue';

    protected $description = 'Mark issued citations as overdue when past due date';

    public function handle(CitationNumberService $service): int
    {
        $count = $service->markOverdueCitations();
        $this->info("Marked {$count} citation(s) as overdue.");

        return self::SUCCESS;
    }
}
