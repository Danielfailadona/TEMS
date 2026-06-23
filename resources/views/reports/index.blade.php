@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-1">Reports & Analytics</h1>
    <p class="text-muted mb-0">System performance and enforcement metrics.</p>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <a href="{{ route('reports.revenue') }}" class="text-decoration-none">
            <div class="card stat-card h-100 border-0">
                <div class="card-body text-center py-5">
                    <i class="bi bi-cash-coin fs-1 text-success mb-3 d-block"></i>
                    <h5 class="card-title">Revenue Report</h5>
                    <p class="text-muted mb-0">Payment collections by period, method, and daily totals.</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('reports.citations') }}" class="text-decoration-none">
            <div class="card stat-card h-100 border-0">
                <div class="card-body text-center py-5">
                    <i class="bi bi-file-earmark-text fs-1 text-primary mb-3 d-block"></i>
                    <h5 class="card-title">Citation Report</h5>
                    <p class="text-muted mb-0">Citations by status, violation type, and issuing officer.</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="{{ route('reports.enforcer-performance') }}" class="text-decoration-none">
            <div class="card stat-card h-100 border-0">
                <div class="card-body text-center py-5">
                    <i class="bi bi-person-badge fs-1 text-info mb-3 d-block"></i>
                    <h5 class="card-title">Enforcer Performance</h5>
                    <p class="text-muted mb-0">Citations issued per enforcer over a period.</p>
                </div>
            </div>
        </a>
    </div>
</div>
@endsection
