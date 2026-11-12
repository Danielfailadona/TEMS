@extends('layouts.app')

@section('title', 'Appeals')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0">Manage citation appeals and review outcomes.</p>
    @can('create', App\Models\Appeal::class)
        <a href="{{ route('appeals.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>File Appeal</a>
    @endcan
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

<section class="std-table-wrap" aria-label="Appeals">
    <table class="std-table table-cards">
        <thead>
            <tr>
                <th scope="col">Ticket #</th>
                <th scope="col">Citation</th>
                <th scope="col">Filed By</th>
                <th scope="col">Reason</th>
                <th scope="col">Status</th>
                <th scope="col">Submitted</th>
                <th scope="col"><span class="visually-hidden">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($appeals as $appeal)
                <tr>
                    <td data-label="Ticket #"><span class="std-pill tag-gray">{{ $appeal->ticketNumber() }}</span></td>
                    <td data-label="Citation">{{ $appeal->citation->citation_number ?? '—' }}</td>
                    <td data-label="Filed By">{{ $appeal->submitterDisplay() }}</td>
                    <td data-label="Reason">{{ \Illuminate\Support\Str::limit($appeal->reason, 60) }}</td>
                    <td data-label="Status"><span class="std-pill {{ $appeal->status->badgeClass() }}">{{ $appeal->status->label() }}</span></td>
                    <td data-label="Submitted">{{ optional($appeal->submitted_at)->format('M d, Y') }}</td>
                    <td>
                        <div class="d-inline-flex align-items-center gap-2">
                            <a href="{{ route('appeals.show', $appeal) }}" class="std-view-btn" title="View appeal" aria-label="View appeal {{ $appeal->ticketNumber() }}">
                                <svg viewBox="0 0 24 24"><path d="M2 12s3.6-6.5 10-6.5S22 12 22 12s-3.6 6.5-10 6.5S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            <x-delete-button :record="$appeal" route-name="appeals.destroy" :label="'Appeal '.$appeal->ticketNumber()" />
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="std-empty">No appeals found.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>

<footer class="std-table-footer">
    <span>Showing {{ $appeals->firstItem() ?? 0 }} to {{ $appeals->lastItem() ?? 0 }} of {{ $appeals->total() }} results</span>
    @if ($appeals->hasPages())
        {{ $appeals->links() }}
    @endif
</footer>
@endsection
