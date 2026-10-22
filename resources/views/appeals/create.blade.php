@extends('layouts.app')

@section('title', 'File Appeal')

@section('content')
<p class="text-muted mb-4">File an appeal on behalf of a citizen presenting a citation at the front desk.</p>

<div class="card stat-card">
    <div class="card-body">
        <form method="POST" action="{{ route('appeals.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Citation</label>
                <select name="citation_id" class="form-select" required>
                    <option value="">Select a citation</option>
                    @foreach ($citations as $citation)
                        <option value="{{ $citation->id }}" @selected(old('citation_id') == $citation->id)>{{ $citation->citation_number }} — {{ $citation->violationLabel() }} ({{ $citation->vehicle_plate }})</option>
                    @endforeach
                </select>
                @error('citation_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Reason</label>
                <input type="text" name="reason" class="form-control" value="{{ old('reason') }}" required maxlength="255">
                @error('reason')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Details</label>
                <textarea name="description" rows="4" class="form-control" maxlength="2000">{{ old('description') }}</textarea>
                @error('description')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>File Appeal</button>
            <a href="{{ route('appeals.index') }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
@endsection
