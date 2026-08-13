@extends('layouts.app')

@section('title', 'Enforcer Performance')

@section('content')
<div class="mb-4">
    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form method="GET" class="row g-2 mb-4">
    <div class="col-auto"><input type="date" name="date_from" class="form-control" value="{{ $from->format('Y-m-d') }}"></div>
    <div class="col-auto"><input type="date" name="date_to" class="form-control" value="{{ $to->format('Y-m-d') }}"></div>
    <div class="col-auto"><button class="btn btn-primary"><i class="bi bi-filter"></i> Filter</button></div>
</form>

<div class="card stat-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Enforcer</th><th>Email</th><th class="text-end">Citations Issued</th></tr></thead>
            <tbody>
                @forelse ($enforcers as $i => $enforcer)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $enforcer->name }}</td>
                        <td>{{ $enforcer->email }}</td>
                        <td class="text-end fw-semibold">{{ $enforcer->issued_citations_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No citations issued in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
