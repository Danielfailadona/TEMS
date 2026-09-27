@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>
    .trend-up { color:#16a34a; }
    .trend-down { color:#dc2626; }
    .trend-flat { color:#6b7280; }
    .pending-card {
        display:flex; align-items:center; gap:0.75rem;
        padding:0.75rem 1rem; border-radius:0.5rem;
        background:var(--itevcms-surface); border:1px solid var(--itevcms-border);
        font-size:0.85rem;
    }
    .pending-count {
        width:2rem; height:2rem; border-radius:0.4rem;
        display:flex; align-items:center; justify-content:center;
        font-weight:700; font-size:0.9rem; flex-shrink:0;
    }
    .chart-box { height:200px; }
    .chart-box-sm { height:150px; }
    .dash-map { width:100%; height:220px; border-radius:0.5rem; overflow:hidden; }
</style>
@endpush

@section('content')
@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');
@endphp

{{-- Header --}}
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-2 mb-4 animate-on-load">
    <div>
        <h1 class="h3 mb-1">{{ $greeting }}, {{ auth()->user()->name }}</h1>
        <div class="d-flex flex-wrap gap-3 text-muted small">
            <span><i class="bi bi-calendar3 me-1"></i>{{ now()->format('l, F j, Y') }}</span>
            <span><i class="bi bi-person-badge me-1"></i>{{ auth()->user()->role->label() }}</span>
        </div>
    </div>
</div>

{{-- KPI Cards (5) --}}
<div class="row g-3 mb-4 animate-on-load row-cols-2 row-cols-md-3 row-cols-xl-5">
    @php
        $kpis = [
            ['icon' => 'bi-file-earmark-text', 'label' => 'Total Citations', 'value' => $stats['total_citations'], 'tone' => 'primary', 'key' => 'total_citations'],
            ['icon' => 'bi-file-earmark-x', 'label' => 'Unpaid Citations', 'value' => $stats['unpaid_citations'], 'tone' => 'warning', 'key' => 'unpaid_citations'],
            ['icon' => 'bi-cash-stack', 'label' => 'Payments Today', 'value' => '₱'.number_format($stats['revenue_today'], 2), 'tone' => 'success', 'key' => 'revenue_today'],
            ['icon' => 'bi-lock', 'label' => 'Active Clamps', 'value' => $stats['active_clamps'], 'tone' => 'danger', 'key' => 'active_clamps'],
            ['icon' => 'bi-exclamation-circle', 'label' => 'Pending Appeals', 'value' => $stats['pending_appeals'], 'tone' => 'info', 'key' => 'pending_appeals'],
        ];
    @endphp
    @foreach ($kpis as $kpi)
        <div class="col">
            <div class="card stat-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div class="stat-icon bg-{{ $kpi['tone'] }}-subtle text-{{ $kpi['tone'] }} rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                            <i class="{{ $kpi['icon'] }} fs-5"></i>
                        </div>
                        @if (isset($trends[$kpi['key']]))
                            <span class="badge bg-{{ $trends[$kpi['key']]['direction'] === 'up' ? 'success' : ($trends[$kpi['key']]['direction'] === 'down' ? 'danger' : 'secondary') }} fs-6 trend-{{ $trends[$kpi['key']]['direction'] }}">
                                <i class="bi bi-arrow-{{ $trends[$kpi['key']]['direction'] === 'up' ? 'up' : ($trends[$kpi['key']]['direction'] === 'down' ? 'down' : 'right') }}-circle me-1"></i>
                                {{ abs($trends[$kpi['key']]['percent']) }}%
                            </span>
                        @endif
                    </div>
                    <h2 class="mb-0 mt-2">{{ $kpi['value'] }}</h2>
                    <p class="text-muted small mb-0">{{ $kpi['label'] }}</p>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Pending Work Queue --}}
<div class="card stat-card mb-4 animate-on-load">
    <div class="card-header bg-white">
        <strong>Pending Work Queue</strong>
        <span class="text-muted small">Needs attention</span>
    </div>
    <div class="card-body d-flex flex-column gap-3">
        <div class="pending-card">
            <div class="pending-count" style="color:#d97706;background:#d9770615;">
                {{ $pendingQueue['waiting_releases'] }}
            </div>
            <div class="flex-grow-1">
                <div class="fw-semibold small">Waiting Releases</div>
                <div class="text-muted" style="font-size:0.7rem;">Vehicles ready for release</div>
            </div>
            <a href="{{ route('impounding.index', ['status' => 'waiting_release']) }}" class="btn btn-sm btn-outline-primary" style="font-size:0.7rem;">View</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Ensure any existing dashboard JS doesn't conflict
});
</script>
@endpush