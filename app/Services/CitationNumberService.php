<?php

namespace App\Services;

use App\Enums\CitationStatus;
use App\Models\Citation;
use Illuminate\Support\Str;

class CitationNumberService
{
    public function generate(): string
    {
        do {
            $number = 'CIT-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (Citation::where('citation_number', $number)->exists());

        return $number;
    }

    public function receiptNumber(): string
    {
        do {
            $number = 'RCP-'.now()->format('YmdHis').'-'.strtoupper(Str::random(4));
        } while (\App\Models\Payment::where('receipt_number', $number)->exists());

        return $number;
    }

    public function noticeNumber(): string
    {
        do {
            $number = 'CLP-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (\App\Models\ClampingRecord::where('notice_number', $number)->exists());

        return $number;
    }

    public function releaseNumber(): string
    {
        return 'REL-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
    }

    public function dueDate(): \Illuminate\Support\Carbon
    {
        return now()->addDays(config('itevcms.citation_overdue_days'));
    }

    public function markOverdueCitations(): int
    {
        return Citation::query()
            ->where('status', CitationStatus::Issued)
            ->whereDate('due_date', '<', now())
            ->update(['status' => CitationStatus::Overdue]);
    }
}
