@extends('layouts.app')

@section('title', 'Review Appeal')

@section('content')
<p class="text-muted mb-4">Update the appeal decision and record review notes.</p>

<div class="card stat-card">
    <div class="card-body">
        <form method="POST" action="{{ route('appeals.update', $appeal) }}">
            @csrf
            @method('PATCH')
            <div class="mb-3">
                <label class="form-label">Decision</label>
                <select name="status" class="form-select" required>
                    <option value="under_review" @selected($appeal->status->value === 'under_review')>Under Review</option>
                    <option value="approved" @selected($appeal->status->value === 'approved')>Approved</option>
                    <option value="rejected" @selected($appeal->status->value === 'rejected')>Rejected</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Adjusted Fine Amount (optional)</label>
                <input type="number" name="adjusted_amount" class="form-control" value="{{ old('adjusted_amount', $appeal->citation?->penalty_amount ?? '') }}" step="0.01" min="0" placeholder="Leave blank if no adjustment">
            </div>
            <div class="mb-3">
                <label class="form-label">Decision Notes</label>
                <textarea name="decision_notes" rows="4" class="form-control">{{ old('decision_notes', $appeal->decision_notes) }}</textarea>
            </div>
            @if ($appeal->reviewed_by)
                <div class="mb-3">
                    <label class="form-label">Reviewed By</label>
                    <p class="form-control-plaintext">{{ $appeal->reviewer->name ?? '—' }}</p>
                </div>
            @endif
            <button type="submit" class="btn btn-primary">Save Review</button>
            <a href="{{ route('appeals.show', $appeal) }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
@endsection
