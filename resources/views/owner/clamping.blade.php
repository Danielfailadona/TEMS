@extends('layouts.app')

@section('title', 'Clamping Status')

@section('content')
<div class="card stat-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Notice #</th><th>Vehicle</th><th>Clamped At</th><th>Status</th><th>Release</th><th></th></tr></thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td>{{ $record->notice_number }}</td>
                        <td>{{ $record->vehicle_plate }}</td>
                        <td>{{ $record->clamped_at->format('M d, Y') }}</td>
                        <td><span class="badge {{ $record->status->badgeClass() }}">{{ $record->status->label() }}</span></td>
                        <td>{{ $record->release?->release_number ?? '—' }}</td>
                        <td class="text-end"><a href="{{ route('clamping.show', $record) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No clamping records for your vehicles.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($records->hasPages())<div class="card-footer bg-white">{{ $records->links() }}</div>@endif
</div>
@endsection
