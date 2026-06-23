@extends('layouts.app')

@section('title', 'Citations')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Citations</h1>
    @can('create', App\Models\Citation::class)
        <a href="{{ route('citations.create') }}" class="btn btn-primary">Issue Citation</a>
    @endcan
</div>

<form class="row g-2 mb-3" method="GET">
    <div class="col-md-4"><input type="search" name="search" class="form-control" placeholder="Citation # or plate..." value="{{ request('search') }}"></div>
    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach (App\Enums\CitationStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-outline-secondary">Filter</button></div>
</form>

<div class="card stat-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Citation #</th>
                    <th>Violation</th>
                    <th>Vehicle</th>
                    <th>Amount</th>
                    <th>Issued</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($citations as $citation)
                    <tr>
                        <td>{{ $citation->citation_number }}</td>
                        <td>{{ $citation->violationType->name }}</td>
                        <td>{{ $citation->vehicle_plate }}</td>
                        <td>₱{{ number_format($citation->penalty_amount, 2) }}</td>
                        <td>{{ $citation->issued_at->format('M d, Y') }}</td>
                        <td><span class="badge {{ $citation->status->badgeClass() }}">{{ $citation->status->label() }}</span></td>
                        <td class="text-end"><a href="{{ route('citations.show', $citation) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No citations found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($citations->hasPages())<div class="card-footer bg-white">{{ $citations->links() }}</div>@endif
</div>
@endsection
