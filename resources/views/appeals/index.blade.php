@extends('layouts.app')

@section('title', 'Appeals')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0">Manage citation appeals and review outcomes.</p>
    @if(auth()->user()->isRole(App\Enums\Role::VehicleOwner))
        <a href="{{ route('appeals.create') }}" class="btn btn-primary">Submit Appeal</a>
    @endif
</div>

<form class="row g-2 mb-3" method="GET">
    <div class="col-md-4">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach (['submitted', 'under_review', 'approved', 'rejected'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
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
                    <th>Citation</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($appeals as $appeal)
                    <tr>
                        <td>{{ $appeal->citation->citation_number ?? '—' }}</td>
                        <td>{{ $appeal->reason }}</td>
                        <td><span class="badge {{ $appeal->status->badgeClass() }}">{{ $appeal->status->label() }}</span></td>
                        <td>{{ optional($appeal->submitted_at)->format('M d, Y') }}</td>
                        <td class="text-end"><a href="{{ route('appeals.show', $appeal) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No appeals found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($appeals->hasPages())<div class="card-footer bg-white">{{ $appeals->links() }}</div>@endif
</div>
@endsection
