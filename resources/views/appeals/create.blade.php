@extends('layouts.app')

@section('title', 'File Appeal')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
    <div>
        <h1 class="h4 mb-0"><i class="bi bi-journal-text me-1"></i>File an Appeal</h1>
        <p class="text-muted small mb-0">Select the citation being appealed, review its details, then file the appeal.</p>
    </div>
    <a href="{{ route('appeals.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Appeals
    </a>
</div>

{{-- Search toolbar --}}
<form method="GET" action="{{ route('appeals.create') }}" class="d-flex flex-wrap gap-2 mb-3" aria-label="Search eligible citations">
    <input type="search" name="search" class="form-control" style="max-width: 340px;"
           placeholder="Citation #, plate, or driver..." value="{{ request('search') }}" aria-label="Search eligible citations">
    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
</form>

{{-- Results --}}
<section class="std-table-wrap" aria-label="Citations eligible for appeal">
    <table class="std-table table-cards">
        <thead>
            <tr>
                <th scope="col">Citation #</th>
                <th scope="col">Plate</th>
                <th scope="col">Violation</th>
                <th scope="col">Penalty</th>
                <th scope="col">Issued</th>
                <th scope="col">Status</th>
                <th scope="col"><span class="visually-hidden">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($citations as $eligible)
                @php
                    $selectUrl = route('appeals.create', array_filter([
                        'citation_id' => $eligible->id,
                        'search' => request('search'),
                    ]));
                    $isSelected = ($citation?->id ?? null) === $eligible->id;
                @endphp
                <tr class="{{ $isSelected ? 'row-selected' : '' }}">
                    <td data-label="Citation #"><strong>{{ $eligible->citation_number }}</strong></td>
                    <td data-label="Plate">{{ $eligible->vehicle_plate }}</td>
                    <td data-label="Violation">{{ $eligible->violationLabel() }}</td>
                    <td data-label="Penalty">₱{{ number_format((float) $eligible->penalty_amount, 2) }}</td>
                    <td data-label="Issued">{{ $eligible->issued_at?->format('M d, Y') ?? '—' }}</td>
                    <td data-label="Status"><span class="std-pill {{ $eligible->status->value === 'overdue' ? 'bg-warning-soft' : 'bg-gray-soft' }}">{{ $eligible->status->label() }}</span></td>
                    <td>
                        <a href="{{ $selectUrl }}" class="btn btn-sm {{ $isSelected ? 'btn-success' : 'btn-primary' }}">
                            @if ($isSelected) <i class="bi bi-check-lg me-1"></i>Selected @else Select @endif
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="std-empty">
                    @if (request('search'))
                        No citations match "{{ request('search') }}". Only Issued or Overdue citations can be appealed.
                    @else
                        No citations are currently eligible for appeal. Only Issued or Overdue citations can be appealed.
                    @endif
                </td></tr>
            @endforelse
        </tbody>
    </table>
</section>

<footer class="std-table-footer">
    <span>{{ $citations->count() }} eligible citation{{ $citations->count() === 1 ? '' : 's' }} found{{ $citations->count() >= 50 ? ' (showing first 50 — refine your search)' : '' }}</span>
</footer>

{{-- Review panel + appeal form (server-rendered for the selected citation) --}}
@if ($citation)
    <div class="card stat-card mt-4" id="reviewPanel">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-file-earmark-text me-1"></i>Selected Citation — Review</strong>
            <span class="std-pill {{ $citation->status->value === 'overdue' ? 'bg-warning-soft' : 'bg-gray-soft' }}">{{ $citation->status->label() }}</span>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-4"><span class="text-muted small d-block">Citation #</span><strong>{{ $citation->citation_number }}</strong></div>
                <div class="col-md-4"><span class="text-muted small d-block">Vehicle Plate</span><strong>{{ $citation->vehicle_plate }}</strong></div>
                <div class="col-md-4"><span class="text-muted small d-block">Violation</span><span>{{ $citation->violationLabel() }}</span></div>
                <div class="col-md-4"><span class="text-muted small d-block">Penalty</span><span>₱{{ number_format((float) $citation->penalty_amount, 2) }}</span></div>
                <div class="col-md-4"><span class="text-muted small d-block">Issued</span><span>{{ $citation->issued_at?->format('M d, Y') ?? '—' }}</span></div>
                <div class="col-md-4"><span class="text-muted small d-block">Due</span><span>{{ $citation->due_date?->format('M d, Y') ?? '—' }}</span></div>
                <div class="col-md-4"><span class="text-muted small d-block">Driver</span><span>{{ $citation->driver_name ?: '—' }}</span></div>
                <div class="col-md-4"><span class="text-muted small d-block">Issuing Officer</span><span>{{ $citation->enforcer?->name ?? '—' }}</span></div>
            </div>

            <h6 class="fw-bold mb-2"><i class="bi bi-send me-1"></i>Next Step — File the Appeal</h6>
            <form method="POST" action="{{ route('appeals.store') }}">@csrf
                <input type="hidden" name="citation_id" value="{{ $citation->id }}">

                <div class="mb-3">
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <input type="text" name="reason" class="form-control" value="{{ old('reason') }}" required maxlength="255" placeholder="e.g. Vehicle was parked legally with a permit">
                    @error('reason')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Details</label>
                    <textarea name="description" rows="4" class="form-control" maxlength="2000" placeholder="Supporting context for the appeal">{{ old('description') }}</textarea>
                    @error('description')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>

                @error('citation_id')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror

                <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>File Appeal</button>
                <a href="{{ route('appeals.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endif
@endsection
