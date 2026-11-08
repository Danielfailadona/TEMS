@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>
    .trend-badge { font-size: 0.75rem; font-weight: 600; padding: 0.15rem 0.4rem; border-radius: 0.35rem; }
    .trend-up { background: rgba(22, 163, 74, 0.15); color: #16a34a; }
    .trend-down { background: rgba(220, 38, 38, 0.15); color: #dc2626; }
    .trend-flat { background: rgba(107, 114, 128, 0.15); color: #6b7280; }
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
    
    .violation-item {
        display:flex; justify-content:space-between; align-items:center;
        padding:0.35rem 0; border-bottom:1px solid #f1f5f9;
        font-size:0.82rem;
    }
    .violation-item:last-child { border-bottom:none; }
    .violation-bar {
        height:5px; border-radius:3px; background:#2563eb;
        transition:width 0.3s ease;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
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
<div class="tier1-row animate-on-load">
    @php
        $attention = [
            ['icon' => 'bi-receipt', 'label' => 'Total Citations', 'value' => $stats['total_citations'], 'key' => 'total_citations', 'black' => true],
            ['icon' => 'bi-file-earmark-x', 'label' => 'Unpaid Citations', 'value' => $stats['unpaid_citations'], 'key' => 'unpaid_citations', 'red' => 30, 'amber_min' => 5],
            ['icon' => 'bi-cash-stack', 'label' => 'Payments Today', 'value' => $stats['revenue_today'], 'key' => 'revenue_today', 'black' => true],
            ['icon' => 'bi-lock', 'label' => 'Active Clamps', 'value' => $stats['active_clamps'], 'key' => 'active_clamps', 'red' => 5, 'amber_min' => 1],
            ['icon' => 'bi-chat-square-text', 'label' => 'Pending Appeals', 'value' => $stats['pending_appeals'], 'key' => 'pending_appeals', 'red' => 10, 'amber_min' => 1],
        ];
    @endphp
    @foreach ($attention as $a)
        @php
            $isBlack = $a['black'] ?? false;
            if ($isBlack) {
                $state = '';
            } else {
                $red = $a['red'] ?? null;
                $amberMin = $a['amber_min'] ?? null;
                if ($a['value'] === 0) {
                    $state = 'state-green';
                } elseif ($amberMin !== null && $a['value'] >= $amberMin && ($red === null || $a['value'] < $red)) {
                    $state = 'state-amber';
                } elseif ($red !== null && $a['value'] >= $red) {
                    $state = 'state-red';
                } else {
                    $state = 'state-amber';
                }
            }
            $stateClass = $isBlack ? '' : ' ' . $state;
            $t = $trends[$a['key']] ?? ['direction' => 'flat', 'percent' => 0];
        @endphp
        <div class="stat-card{{ $stateClass }}">
            <div class="stat-label">
                @if (!$isBlack)
                    <span class="state-dot" aria-hidden="true"></span>
                @endif
                <i class="bi {{ $a['icon'] }}"></i>
                {{ $a['label'] }}
            </div>
            @if ($a['key'] === 'revenue_today')
                <div class="stat-value">₱{{ number_format($a['value'], 2) }}</div>
            @else
                <div class="stat-value">{{ number_format($a['value']) }}</div>
            @endif
            <div class="stat-trend">
                @if ($t['direction'] === 'up')
                    <span class="trend-badge trend-up"><i class="bi bi-arrow-up-short"></i>{{ $t['percent'] }}%</span>
                @elseif ($t['direction'] === 'down')
                    <span class="trend-badge trend-down"><i class="bi bi-arrow-down-short"></i>{{ $t['percent'] }}%</span>
                @else
                    <span class="trend-badge trend-flat"><i class="bi bi-dash"></i>0%</span>
                @endif
                <span class="text-muted ms-1">vs last week</span>
            </div>
        </div>
    @endforeach
</div>

{{-- Analytics 2x2 Grid --}}
<div class="row g-4 mb-4 animate-on-load">
    {{-- Citations by Month --}}
    <div class="col-md-6">
        <div class="card stat-card h-100">
            <div class="card-header bg-white"><strong>Citations by Month</strong></div>
            <div class="card-body chart-box">
                <canvas id="citationsChart"></canvas>
            </div>
        </div>
    </div>
    {{-- Payments Trend --}}
    <div class="col-md-6">
        <div class="card stat-card h-100">
            <div class="card-header bg-white"><strong>Payments Trend</strong></div>
            <div class="card-body chart-box">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>
    {{-- Top Violations --}}
    <div class="col-md-6">
        <div class="card stat-card h-100">
            <div class="card-header bg-white"><strong>Top Violation Types</strong></div>
            <div class="card-body">
                @if ($topViolations->isEmpty())
                    <div class="text-muted text-center py-4 small">No data for the last 3 months.</div>
                @else
                    @php $maxCount = $topViolations->max('count'); @endphp
                    @foreach ($topViolations as $v)
                        <div class="violation-item">
                            <span>{{ $v['name'] }}</span>
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:80px;">
                                    <div class="violation-bar" style="width:{{ $maxCount > 0 ? ($v['count']/$maxCount)*100 : 0 }}%;"></div>
                                </div>
                                <span class="fw-semibold small">{{ $v['count'] }}</span>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
    {{-- Appeals Trend --}}
    <div class="col-md-6">
        <div class="card stat-card h-100">
            <div class="card-header bg-white"><strong>Appeals Trend</strong></div>
            <div class="card-body chart-box">
                <canvas id="appealsChart"></canvas>
            </div>
        </div>
    </div>
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
@vite('resources/js/zone-picker.js')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const citationLabels = @json($citationsByMonth->keys()->values());
    const citationData = @json($citationsByMonth->values());
    const revenueLabels = @json($revenueByMonth->keys()->values());
    const revenueData = @json($revenueByMonth->values());
    const appealLabels = @json($appealsByMonth->keys()->values());
    const appealData = @json($appealsByMonth->values());

    // Robust Chart.js initialization with fallback
    function waitForChart(attempts = 0) {
        return new Promise((resolve) => {
            if (typeof Chart !== 'undefined') {
                resolve(Chart);
            } else if (attempts < 50) {
                setTimeout(() => waitForChart(attempts + 1).then(resolve), 50);
            } else {
                // Fallback: load Chart.js from CDN
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
                script.onload = () => resolve(window.Chart);
                script.onerror = () => resolve(null);
                document.head.appendChild(script);
            }
        });
    }

    async function initCharts() {
        const Chart = await waitForChart();
        if (!Chart) {
            console.warn('Chart.js failed to load, charts disabled');
            return;
        }

        const citationEl = document.getElementById('citationsChart');
        if (citationEl) {
            try {
                new Chart(citationEl, {
                    type: 'bar',
                    data: {
                        labels: citationLabels,
                        datasets: [{ label: 'Citations', data: citationData, backgroundColor: '#2563eb', borderRadius: 4 }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                    }
                });
            } catch (e) { console.warn('Citation chart failed:', e); }
        }

        const revenueEl = document.getElementById('revenueChart');
        if (revenueEl) {
            try {
                new Chart(revenueEl, {
                    type: 'line',
                    data: {
                        labels: revenueLabels,
                        datasets: [{ label: 'Payments (₱)', data: revenueData, borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,0.08)', fill: true, tension: 0.35 }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { callback: v => '₱' + Number(v).toLocaleString() } } }
                    }
                });
            } catch (e) { console.warn('Revenue chart failed:', e); }
        }

        const appealsEl = document.getElementById('appealsChart');
        if (appealsEl) {
            try {
                new Chart(appealsEl, {
                    type: 'line',
                    data: {
                        labels: appealLabels,
                        datasets: [{ label: 'Appeals', data: appealData, borderColor: '#7c3aed', backgroundColor: 'rgba(124,58,237,0.08)', fill: true, tension: 0.35 }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                    }
                });
            } catch (e) { console.warn('Appeals chart failed:', e); }
        }
    }

    initCharts();
</script>
@endpush