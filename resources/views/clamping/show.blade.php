@extends('layouts.app')

@section('title', $clamping->notice_number)

@section('content')
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h3 mb-1">Clamp Notice {{ $clamping->notice_number }}</h1>
        <p class="text-muted mb-0">Vehicle: {{ $clamping->vehicle_plate }}</p>
    </div>
    <span class="badge {{ $clamping->status->badgeClass() }} fs-6">{{ $clamping->status->label() }}</span>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card stat-card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><strong class="text-muted small d-block">Officer</strong>{{ $clamping->officer->name }}</div>
                    <div class="col-md-6"><strong class="text-muted small d-block">Clamped At</strong>{{ $clamping->clamped_at->format('M d, Y h:i A') }}</div>
                    <div class="col-md-6"><strong class="text-muted small d-block">Location</strong>{{ $clamping->location ?? '—' }}</div>
                    <div class="col-md-6"><strong class="text-muted small d-block">Related Citation</strong>{{ $clamping->citation?->citation_number ?? '—' }}</div>
                    @if ($clamping->notes)
                        <div class="col-12"><strong class="text-muted small d-block">Notes</strong>{{ $clamping->notes }}</div>
                    @endif
                </div>
                @if ($clamping->evidence_path)
                    <hr>
                    <img src="{{ asset('storage/'.$clamping->evidence_path) }}" alt="Clamp evidence" class="rounded border" style="max-height:200px">
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        @if (in_array($clamping->status, [App\Enums\ClampingStatus::AwaitingPayment, App\Enums\ClampingStatus::Paid, App\Enums\ClampingStatus::WaitingRelease]))
            <div class="card stat-card">
                <div class="card-body">
                    <a href="{{ route('impounding.show', $clamping) }}" class="btn btn-primary w-100">View in Impounding</a>
                </div>
            </div>
        @elseif ($clamping->release)
            <div class="card stat-card">
                <div class="card-header bg-white"><strong>Release</strong></div>
                <div class="card-body">
                    <p class="mb-1">{{ $clamping->release->release_number }}</p>
                    <p class="mb-0 text-muted small">{{ $clamping->release->released_at->format('M d, Y') }}</p>
                </div>
            </div>
        @elseif ($clamping->status === App\Enums\ClampingStatus::Released)
            <div class="card stat-card">
                <div class="card-header bg-white"><strong>Release</strong></div>
                <div class="card-body">
                    <p class="mb-1">{{ $clamping->release?->release_number ?? 'Released' }}</p>
                    <p class="mb-0 text-muted small">{{ $clamping->release?->released_at?->format('M d, Y') ?? '—' }}</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
