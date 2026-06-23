<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArchiveController extends Controller
{
    public function index(Request $request): View
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

        $archives = $query->paginate(12);

        $types = Archive::select('archivable_type')
            ->distinct()
            ->pluck('archivable_type')
            ->map(fn ($t) => class_basename($t))
            ->sort()
            ->values();

        return view('archives.index', compact('archives', 'types', 'user'));
    }
}
