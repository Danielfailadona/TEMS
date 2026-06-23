<?php

namespace App\Http\Controllers;

use App\Enums\AppealStatus;
use App\Http\Requests\StoreAppealRequest;
use App\Http\Requests\UpdateAppealRequest;
use App\Models\Appeal;
use App\Models\Archive;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AppealController extends Controller
{
    public function index(Request $request): View
    {
        $query = Appeal::with(['citation', 'submitter', 'reviewer']);

        if (! auth()->user()->isStaff()) {
            $query->where('submitted_by', auth()->id());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $appeals = $query->latest('submitted_at')->paginate(10)->withQueryString();

        return view('appeals.index', compact('appeals'));
    }

    public function create(): View
    {
        $this->authorize('create', Appeal::class);

        $citations = Citation::with(['violationType'])
            ->whereIn('status', [\App\Enums\CitationStatus::Issued, \App\Enums\CitationStatus::Overdue])
            ->latest('issued_at')
            ->get();

        return view('appeals.create', compact('citations'));
    }

    public function store(StoreAppealRequest $request): RedirectResponse
    {
        $this->authorize('create', Appeal::class);

        $citation = Citation::findOrFail($request->citation_id);

        $existing = Appeal::where('citation_id', $citation->id)
            ->where('submitted_by', auth()->id())
            ->exists();

        if ($existing) {
            return back()->withErrors(['citation_id' => 'You have already submitted an appeal for this citation.']);
        }

        if ($citation->isPaid()) {
            return back()->withErrors(['citation_id' => 'This citation has already been paid and cannot be appealed.']);
        }

        Appeal::create([
            'citation_id' => $citation->id,
            'submitted_by' => auth()->id(),
            'reason' => $request->reason,
            'description' => $request->description,
            'status' => AppealStatus::Submitted,
            'submitted_at' => now(),
        ]);

        return redirect()->route('appeals.index')->with('success', 'Appeal submitted successfully.');
    }

    public function show(Appeal $appeal): View
    {
        $this->authorize('view', $appeal);

        $appeal->load(['citation.violationType', 'submitter', 'reviewer']);

        return view('appeals.show', compact('appeal'));
    }

    public function edit(Appeal $appeal): View
    {
        $this->authorize('update', $appeal);

        return view('appeals.edit', compact('appeal'));
    }

    public function update(UpdateAppealRequest $request, Appeal $appeal): RedirectResponse
    {
        $this->authorize('update', $appeal);

        DB::transaction(function () use ($request, $appeal) {
            $appeal->update([
                'status' => $request->status,
                'reviewed_by' => auth()->id(),
                'decision_notes' => $request->decision_notes,
                'reviewed_at' => now(),
            ]);

            if ($request->status === 'approved' && $request->filled('adjusted_amount')) {
                $appeal->citation->update([
                    'penalty_amount' => $request->adjusted_amount,
                ]);
            }

            if (in_array($request->status, ['approved', 'rejected'], true)) {
                Archive::create([
                    'archivable_type' => Appeal::class,
                    'archivable_id' => $appeal->id,
                    'archived_by' => auth()->id(),
                    'archived_at' => now(),
                    'reason' => 'Appeal '.$request->status,
                    'snapshot' => $appeal->refresh()->toArray(),
                ]);
            }
        });

        return redirect()->route('appeals.show', $appeal)->with('success', 'Appeal updated successfully.');
    }
}
