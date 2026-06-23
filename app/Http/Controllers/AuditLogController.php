<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super_admin,administrator');
    }

    public function index(Request $request): View
    {
        $query = Activity::with(['causer', 'subject'])->latest();

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%{$s}%")
                  ->orWhere('subject_type', 'like', "%{$s}%");
            });
        }

        $activities = $query->paginate(30);

        $logNames = Activity::distinct('log_name')->pluck('log_name')->filter();
        $events = Activity::distinct('event')->pluck('event')->filter();
        $users = \App\Models\User::whereIn('id', Activity::distinct('causer_id')->pluck('causer_id')->filter())->get();

        return view('audit-logs.index', compact('activities', 'logNames', 'events', 'users'));
    }
}
