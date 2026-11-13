<?php

namespace App\Http\Controllers;

use App\Enums\AppealStatus;
use App\Enums\CitationStatus;
use App\Enums\Role;
use App\Http\Requests\StoreAppealRequest;
use App\Http\Requests\UpdateAppealRequest;
use App\Models\Appeal;
use App\Models\Archive;
use App\Models\Citation;
use App\Models\SystemNotification;
use App\Models\User;
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

    public function create(Request $request): View
    {
        $this->authorize('create', Appeal::class);

        // Selected citation survives validation errors via old('citation_id').
        $citationId = $request->input('citation_id') ?? old('citation_id');
        $citation = $citationId
            ? Citation::with(['violationType', 'enforcer'])->find($citationId)
            : null;

        // Citations eligible for appeal: still Issued/Overdue. Searchable so the
        // list stays manageable at any volume.
        $query = Citation::with(['violationType'])
            ->whereIn('status', [CitationStatus::Issued, CitationStatus::Overdue]);

        if ($request->filled('search')) {
            $term = '%'.mb_strtolower(trim($request->search)).'%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(citation_number) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(vehicle_plate) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(driver_name) LIKE ?', [$term]);
            });
        }

        $citations = $query->orderByDesc('issued_at')->limit(50)->get();

        return view('appeals.create', compact('citations', 'citation'));
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

        $appeal = Appeal::create([
            'citation_id' => $citation->id,
            'submitted_by' => auth()->id(),
            'reason' => $request->reason,
            'description' => $request->description,
            'status' => AppealStatus::Submitted,
            'submitted_at' => now(),
        ]);

        // Notify FrontDesk and Admins
        $recipients = User::whereIn('role', [Role::SuperAdmin, Role::Administrator, Role::FrontDesk])->get();
        foreach ($recipients as $recipient) {
            SystemNotification::notify(
                $recipient,
                'appeal_filed',
                'New Appeal Filed',
                "An appeal has been filed for citation {$citation->citation_number} by {$citation->driver_name}. Reason: {$request->reason}",
                ['appeal_id' => $appeal->id, 'citation_id' => $citation->id]
            );
        }

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

        $voidedCheckout = false;

        DB::transaction(function () use ($request, $appeal, &$voidedCheckout) {
            $wasApproved = $appeal->status === AppealStatus::Approved;
            $isNowApproved = $request->status === 'approved';

            $statusEnum = $isNowApproved ? AppealStatus::Approved : ($request->status === 'rejected' ? AppealStatus::Rejected : AppealStatus::UnderReview);

            $appeal->update([
                'status' => $statusEnum,
                'reviewed_by' => auth()->id(),
                'decision_notes' => $request->decision_notes,
                'reviewed_at' => now(),
            ]);

            if ($isNowApproved && ! $wasApproved) {
                $citation = $appeal->citation;
                if ($citation && ! $citation->isPaid()) {
                    $originalAmount = $citation->penalty_amount;
                    $adjustedAmount = $request->filled('adjusted_amount')
                        ? (float) $request->adjusted_amount
                        : round((float) $originalAmount * (1 - Appeal::APPROVED_DISCOUNT_PERCENT / 100), 2);

                    if ($adjustedAmount < 0) {
                        $adjustedAmount = 0;
                    }

                    $appeal->update([
                        'original_amount' => $originalAmount,
                        'adjusted_amount' => $adjustedAmount,
                    ]);

                    $citation->update([
                        'penalty_amount' => $adjustedAmount,
                    ]);

                    // Void any pending (unpaid) online checkout — the old PayMongo
                    // session would still collect the pre-discount total, so a
                    // fresh checkout must be started at the adjusted amount.
                    if ($citation->payment()->whereNull('paid_at')->delete()) {
                        $voidedCheckout = true;
                    }
                } elseif ($citation) {
                    $appeal->update([
                        'original_amount' => $appeal->original_amount ?? $citation->penalty_amount,
                        'adjusted_amount' => $appeal->adjusted_amount ?? $citation->penalty_amount,
                    ]);
                }
            } elseif ($isNowApproved && $wasApproved) {
                $citation = $appeal->citation;
                if ($citation && ! $citation->isPaid() && $request->filled('adjusted_amount')) {
                    $adjustedAmount = (float) $request->adjusted_amount;
                    if ($adjustedAmount < 0) {
                        $adjustedAmount = 0;
                    }

                    $appeal->update([
                        'original_amount' => $appeal->original_amount ?? $citation->penalty_amount,
                        'adjusted_amount' => $adjustedAmount,
                    ]);

                    $citation->update([
                        'penalty_amount' => $adjustedAmount,
                    ]);

                    if ($citation->payment()->whereNull('paid_at')->delete()) {
                        $voidedCheckout = true;
                    }
                }
            }

            if (in_array($request->status, ['approved', 'rejected'], true)) {
                $appeal = $appeal->refresh();
                Archive::log($appeal, 'Appeal '.$request->status, auth()->id());

                // Notify the appellant
                if ($appeal->submitted_by) {
                    $appellant = User::find($appeal->submitted_by);
                    if ($appellant) {
                        SystemNotification::notify(
                            $appellant,
                            'appeal_reviewed',
                            'Appeal '.ucfirst($request->status),
                            "Your appeal for citation {$appeal->citation->citation_number} has been {$request->status}. ".($appeal->decision_notes ? 'Notes: '.$appeal->decision_notes : ''),
                            ['appeal_id' => $appeal->id, 'citation_id' => $appeal->citation_id]
                        );
                    }
                }
            }
        });

        $message = 'Appeal updated successfully.';
        if ($voidedCheckout) {
            $message .= ' The pending online checkout for the old amount was voided — a new checkout at the adjusted amount can be started from the ticket.';
        }

        return redirect()->route('appeals.show', $appeal)->with('success', $message);
    }

    public function destroy(Appeal $appeal): RedirectResponse
    {
        $this->authorize('delete', $appeal);

        if ($appeal->status === AppealStatus::Approved) {
            return back()->with('error', 'Cannot delete appeal '.$appeal->ticketNumber().' because it has been approved (its penalty adjustment is applied to the citation).');
        }

        Archive::log($appeal, 'Appeal deleted', auth()->id(), $appeal->toArray());

        $appeal->delete();

        return redirect()->route('appeals.index')
            ->with('success', 'Appeal '.$appeal->ticketNumber().' has been deleted and archived.');
    }
}
