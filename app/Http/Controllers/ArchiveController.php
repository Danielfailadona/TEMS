<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Exports\ArchivesExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArchiveController extends Controller
{
    private function filteredQuery(Request $request)
    {
        $user = auth()->user();
        $query = Archive::with('archivedBy')->latest('archived_at');

        if ($user->isAdmin()) {
            if ($request->filled('user_id')) {
                $query->where('archived_by', $request->user_id);
            }
        } else {
            $query->where('archived_by', $user->id);
        }

        if ($request->filled('type')) {
            $query->where('archivable_type', $request->type);
        }

        // Date range filters - convert user input (PHT) to UTC for comparison
        if ($request->filled('date_from')) {
            $dateFrom = Carbon::parse($request->date_from)->startOfDay()->setTimezone('UTC');
            $query->where('archived_at', '>=', $dateFrom);
        }
        if ($request->filled('date_to')) {
            $dateTo = Carbon::parse($request->date_to)->endOfDay()->setTimezone('UTC');
            $query->where('archived_at', '<=', $dateTo);
        }

        // Search across title, type, archived_by name, reason, archived_at date range
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhere('archivable_type', 'like', "%{$search}%")
                  ->orWhereHas('archivedBy', fn ($q) => $q->where('name', 'like', "%{$search}%"));

                // Only apply date filter if search looks like a valid date (YYYY-MM-DD)
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $search)) {
                    $q->orWhereDate('archived_at', '=', $search);
                }
            });
        }

        return $query;
    }

    public function index(Request $request): View
    {
        $query = $this->filteredQuery($request);

        $archives = $query->paginate(20)->withQueryString();

        $types = Archive::select('archivable_type')
            ->distinct()
            ->pluck('archivable_type')
            ->map(fn ($t) => class_basename($t))
            ->sort()
            ->values();

        return view('archives.index', compact('archives', 'types'));
    }

    public function print(Archive $archive): View
    {
        $archive->load('archivedBy');
        $type = class_basename($archive->archivable_type);

        return view('archives.print', compact('archive', 'type'));
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $from = $request->date_from ? Carbon::parse($request->date_from)->format('Y-m-d') : 'all';
        $to   = $request->date_to ? Carbon::parse($request->date_to)->format('Y-m-d') : 'all';
        $filename = "archives-report-{$from}-to-{$to}.xlsx";

        return Excel::download(new ArchivesExport($request), $filename);
    }

    public function backup(Request $request): StreamedResponse
    {
        $archives = $this->filteredQuery($request)->get();

        $callback = function () use ($archives) {
            $handle = fopen('php://output', 'w');

            foreach ($archives as $archive) {
                $line = json_encode([
                    'id' => $archive->id,
                    'type' => class_basename($archive->archivable_type),
                    'reason' => $archive->reason,
                    'archived_by' => $archive->archivedBy?->name ?? 'System',
                    'archived_at' => $archive->archived_at->toISOString(),
                    'snapshot' => $archive->snapshot,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                fwrite($handle, $line . "\n");
            }

            fclose($handle);
        };

        $filename = 'archives-backup-' . now()->format('Y-m-d') . '.json';

        return response()->stream($callback, 200, [
            'Content-Type' => 'application/x-ndjson',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
