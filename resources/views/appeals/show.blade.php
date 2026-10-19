@extends('layouts.app')

@section('title', 'Appeal Details')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h3 mb-1">Appeal #{{ $appeal->id }}</h1>
        <p class="text-muted mb-0">Appeal Details</p>
    </div>
    <span class="badge {{ $appeal->status->badgeClass() }} fs-6">{{ $appeal->status->label() }}</span>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card stat-card">
            <div class="card-body">
                <h5 class="card-title">Appeal Summary</h5>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Citation</dt>
                    <dd class="col-sm-8">{{ $appeal->citation->citation_number ?? '—' }} ({{ $appeal->citation->vehicle_plate ?? '—' }})</dd>
                    <dt class="col-sm-4">Submitted By</dt>
                    <dd class="col-sm-8">
                        {{ $appeal->submitterDisplay() }}
                        @if ($appeal->isGuestSubmission() && $appeal->guest_contact)
                            <br><small class="text-muted">{{ $appeal->guest_contact }}</small>
                        @endif
                    </dd>
                    <dt class="col-sm-4">Reason</dt>
                    <dd class="col-sm-8">{{ $appeal->reason }}</dd>
                    <dt class="col-sm-4">Details</dt>
                    <dd class="col-sm-8">{{ $appeal->description ?? '—' }}</dd>
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8"><span class="badge {{ $appeal->status->badgeClass() }}">{{ $appeal->status->label() }}</span></dd>
                    @php
                        $original = $appeal->original_amount ?? ($appeal->citation?->penalty_amount ?? 0);
                        $final = $appeal->adjusted_amount ?? ($appeal->citation?->penalty_amount ?? $original);
                    @endphp
                    <dt class="col-sm-4">Original Amount</dt>
                    <dd class="col-sm-8">₱{{ number_format($original, 2) }}</dd>
                    @if ($appeal->status->value === 'approved')
                        <dt class="col-sm-4">Final Amount</dt>
                        <dd class="col-sm-8">₱{{ number_format($final, 2) }}</dd>
                    @endif
                    <dt class="col-sm-4">Decision Notes</dt>
                    <dd class="col-sm-8">{{ $appeal->decision_notes ?? '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
