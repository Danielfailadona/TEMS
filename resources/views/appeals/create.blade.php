@extends('layouts.app')

@section('title', 'Submit Appeal')

@section('content')
<p class="text-muted mb-4">Request a review for a citation you believe is incorrect.</p>

<div class="card stat-card">
    <div class="card-body">
        <form method="POST" action="{{ route('appeals.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Citation</label>
                <select name="citation_id" class="form-select" required>
                    <option value="">Select a citation</option>
                    @foreach ($citations as $citation)
                        <option value="{{ $citation->id }}">{{ $citation->citation_number }} — {{ $citation->violationType->name }} ({{ $citation->vehicle_plate }})</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Reason</label>
                <input type="text" name="reason" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Details</label>
                <textarea name="description" rows="4" class="form-control"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Appeal</button>
            <a href="{{ route('appeals.index') }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
@endsection
