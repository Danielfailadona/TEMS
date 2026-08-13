@extends('layouts.app')

@section('title', 'Advanced Search')

@section('content')

<form method="GET" class="card stat-card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Search Query</label>
                <input type="text" name="q" class="form-control" placeholder="Citation number, plate number, driver name..." value="{{ $query ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Type</label>
                <select name="type" class="form-select">
                    <option value="all" {{ ($type ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                    <option value="citation" {{ ($type ?? '') === 'citation' ? 'selected' : '' }}>Citations</option>
                    <option value="vehicle" {{ ($type ?? '') === 'vehicle' ? 'selected' : '' }}>Vehicles</option>
                </select>
            </div>
            <div class="col-md-3 align-self-end">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-2"></i>Search</button>
            </div>
        </div>
    </div>
</form>

@if ($query && $results->isNotEmpty())
    <div class="card stat-card">
        <div class="card-header bg-white d-flex justify-content-between">
            <strong>{{ $results->count() }} result(s) for "{{ $query }}"</strong>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#saveSearchModal"><i class="bi bi-bookmark-plus"></i> Save Search</button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Type</th><th>Result</th><th>Detail</th><th></th></tr></thead>
                <tbody>
                    @foreach ($results as $result)
                        <tr>
                            <td><span class="badge bg-{{ $result['type'] === 'Citation' ? 'primary' : 'info' }}">{{ $result['type'] }}</span></td>
                            <td>{{ $result['label'] }}</td>
                            <td class="small text-muted">{{ $result['subtitle'] }}</td>
                            <td class="text-end"><a href="{{ $result['url'] }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@elseif ($query)
    <div class="text-center text-muted py-5">
        <i class="bi bi-search fs-1 d-block mb-2"></i>
        <p>No results found for "{{ $query }}".</p>
    </div>
@endif

@if ($savedSearches->isNotEmpty())
    <div class="card stat-card mt-4">
        <div class="card-header bg-white"><strong>Saved Searches</strong></div>
        <div class="list-group list-group-flush">
            @foreach ($savedSearches as $saved)
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>{{ $saved->name }}</strong>
                        <small class="text-muted ms-2">{{ $saved->type }}</small>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('search.index', array_merge($saved->criteria, ['type' => $saved->type])) }}" class="btn btn-sm btn-outline-primary">Run</a>
                        <form method="POST" action="{{ route('search.destroy', $saved) }}" class="d-inline" onsubmit="return confirm('Delete saved search?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- Save Search Modal --}}
@if ($query)
    <div class="modal fade" id="saveSearchModal" tabindex="-1">
        <form method="POST" action="{{ route('search.save') }}" class="modal-content">
            @csrf
            <input type="hidden" name="type" value="{{ $type ?? 'all' }}">
            <input type="hidden" name="criteria" value="{{ json_encode(array_merge(request()->only(['q', 'type']), ['query' => $query])) }}">
            <div class="modal-header">
                <h5 class="modal-title">Save Search</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Search Name</label>
                <input type="text" name="name" class="form-control" placeholder="e.g., Unpaid John" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
@endif
@endsection
