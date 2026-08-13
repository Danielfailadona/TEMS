@extends('layouts.app')

@section('title', 'My Citations')

@section('content')
<div class="card stat-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Citation #</th><th>Violation</th><th>Vehicle</th><th>Amount</th><th>Due Date</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse ($citations as $citation)
                    <tr>
                        <td>{{ $citation->citation_number }}</td>
                        <td>{{ $citation->violationType->name }}</td>
                        <td>{{ $citation->vehicle_plate }}</td>
                        <td>₱{{ number_format($citation->penalty_amount, 2) }}</td>
                        <td>{{ $citation->due_date->format('M d, Y') }}</td>
                        <td><span class="badge {{ $citation->status->badgeClass() }}">{{ $citation->status->label() }}</span></td>
                        <td class="text-end"><a href="{{ route('citations.show', $citation) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No citations on your record.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($citations->hasPages())<div class="card-footer bg-white">{{ $citations->links() }}</div>@endif
</div>
@endsection
