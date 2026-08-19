@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/maplibre-gl@5.24.0/dist/maplibre-gl.css">
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

@section('content')
@php
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');
    $todaysTotal = $stats['revenue_today'];
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
            ['icon' => 'bi-receipt', 'label' => 'Total Citations', 'value' => number_format($stats['total_citations']), 'tone' => 'primary', 'key' => 'total_citations'],
            ['icon' => 'bi-exclamation-circle', 'label' => 'Unpaid Citations', 'value' => number_format($stats['unpaid_citations']), 'tone' => 'warning', 'key' => 'unpaid_citations'],
            ['icon' => 'bi-cash-stack', 'label' => 'Payments Today', 'value' => '₱'.number_format($stats['revenue_today'], 2), 'tone' => 'success', 'key' => 'revenue_today'],
            ['icon' => 'bi-lock', 'label' => 'Active Clamps', 'value' => number_format($stats['active_clamps']), 'tone' => 'danger', 'key' => 'active_clamps'],
            ['icon' => 'bi-chat-square-text', 'label' => 'Pending Appeals', 'value' => number_format($stats['pending_appeals']), 'tone' => 'info', 'key' => 'pending_appeals'],
        ];
    @endphp
    @foreach ($kpis as $k)
        @php $t = $trends[$k['key']] ?? ['direction' => 'flat', 'percent' => 0]; @endphp
        <div class="col">
            <div class="card stat-card border-0 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 text-muted small mb-1">
                        <i class="bi {{ $k['icon'] }} text-{{ $k['tone'] }}"></i>
                        <span>{{ $k['label'] }}</span>
                    </div>
                    <div class="h3 mb-0 text-{{ $k['tone'] }}">{{ $k['value'] }}</div>
                    <div class="small mt-1">
                        @if ($t['direction'] === 'up')
                            <span class="trend-up"><i class="bi bi-arrow-up-short"></i>{{ $t['percent'] }}%</span>
                        @elseif ($t['direction'] === 'down')
                            <span class="trend-down"><i class="bi bi-arrow-down-short"></i>{{ $t['percent'] }}%</span>
                        @else
                            <span class="trend-flat"><i class="bi bi-dash"></i>0%</span>
                        @endif
                        <span class="text-muted">vs last week</span>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Enforcer GPS Location --}}
@if (auth()->user()->isRole(\App\Enums\Role::Enforcer, \App\Enums\Role::ClampingOfficer))
<div class="row g-3 mb-4 animate-on-load">
    <div class="col-12 col-md-6">
        <div class="card stat-card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-geo-alt me-2"></i>GPS Location</strong>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0"
                          style="width:44px;height:44px;background:var(--bs-primary-bg-subtle);">
                        <i class="bi bi-satellite text-primary"></i>
                    </span>
                    <div>
                        <div class="fw-semibold">Track your location</div>
                        <div class="text-muted small">Update your current GPS position for dispatch tracking</div>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="gps-toggle">
                        <label class="form-check-label fw-semibold" for="gps-toggle">GPS Tracking</label>
                    </div>
                </div>
                <div id="gps-status" class="mb-2">
                    <span class="badge bg-secondary">Tracking paused</span>
                </div>
                <div class="d-flex align-items-center gap-2 mb-2" id="gps-controls" style="display:none !important;">
                    <button type="button" id="gps-update-now" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-geo-alt-fill me-1"></i>Update Now
                    </button>
                    <select id="gps-interval" class="form-select form-select-sm" style="width:auto;">
                        <option value="5000" selected>Every 5s</option>
                        <option value="3000">Every 3s (fast)</option>
                        <option value="15000">Every 15s</option>
                    </select>
                </div>
                <div class="form-text">Requires GPS permission. Your location is sent to the tracking system for dispatch.</div>
            </div>
        </div>
    </div>
</div>
@endif

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
    {{-- Revenue Trend --}}
    @if (!auth()->user()->isRole(\App\Enums\Role::Enforcer, \App\Enums\Role::ClampingOfficer))
    <div class="col-md-6">
        <div class="card stat-card h-100">
            <div class="card-header bg-white"><strong>Revenue Trend</strong></div>
            <div class="card-body chart-box">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>
    @endif
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

{{-- Recent Activity (full width) — admin only --}}
@if (!auth()->user()->isRole(\App\Enums\Role::Enforcer, \App\Enums\Role::ClampingOfficer))
<div class="card stat-card mb-4 animate-on-load">
    <div class="card-header bg-white">
        <strong>Recent Activity</strong>
        <p class="text-muted small mb-0">Latest enforcement events across the office</p>
    </div>
    <div class="card-body">
        @forelse ($recentActivity as $item)
            <div class="d-flex align-items-start justify-content-between gap-3 border-bottom py-3">
                <div class="d-flex gap-3 min-width-0 flex-grow-1">
                    <div class="activity-icon flex-shrink-0"><i class="bi {{ $item['icon'] }}"></i></div>
                    <div class="min-width-0">
                        <div class="fw-semibold small">{{ $item['title'] }}</div>
                        <div class="text-muted small">{{ $item['description'] }}</div>
                        @if (!empty($item['meta']))
                            <div class="text-muted" style="font-size:0.7rem;">{{ $item['meta'] }}</div>
                        @endif
                    </div>
                </div>
                <div class="text-muted small text-nowrap flex-shrink-0">{{ $item['timestamp_label'] }}</div>
            </div>
        @empty
            <div class="text-muted text-center py-4">No recent activity yet.</div>
        @endforelse
    </div>
</div>
@endif

{{-- Pending Work Queue + Quick Actions + Zone Map --}}
<div class="row g-4 animate-on-load">
    <div class="col-xl-4">
        <div class="card stat-card h-100">
            <div class="card-header bg-white">
                <strong>Pending Work Queue</strong>
                <span class="text-muted small">Needs attention</span>
            </div>
            <div class="card-body d-flex flex-column gap-3">
                <div class="pending-card">
                    <div class="pending-count" style="color:#7c3aed;background:#7c3aed15;">
                        {{ $pendingQueue['appeals'] }}
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold small">Pending Appeals</div>
                        <div class="text-muted" style="font-size:0.7rem;">Awaiting review decision</div>
                    </div>
                    <a href="{{ route('appeals.index') }}" class="btn btn-sm btn-outline-primary" style="font-size:0.7rem;">View</a>
                </div>

                <div class="pending-card">
                    <div class="pending-count" style="color:#0891b2;background:#0891b215;">
                        {{ $pendingQueue['clamping_requests'] }}
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold small">Clamping Requests</div>
                        <div class="text-muted" style="font-size:0.7rem;">Citizen requests to review</div>
                    </div>
                    <a href="{{ route('clamping-requests.index') }}" class="btn btn-sm btn-outline-primary" style="font-size:0.7rem;">View</a>
                </div>

                @if (!auth()->user()->isRole(\App\Enums\Role::Enforcer, \App\Enums\Role::ClampingOfficer))
                <div class="pending-card">
                    <div class="pending-count" style="color:#d97706;background:#d9770615;">
                        {{ $pendingQueue['account_approvals'] }}
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold small">Account Approvals</div>
                        <div class="text-muted" style="font-size:0.7rem;">New users awaiting approval</div>
                    </div>
                    <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-primary" style="font-size:0.7rem;">View</a>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card stat-card h-100">
            <div class="card-header bg-white">
                <strong>Quick Actions</strong>
            </div>
            <div class="card-body d-grid gap-2">
                @can('create', App\Models\Citation::class)
                    <a href="{{ route('citations.create') }}" class="btn btn-outline-primary text-start"><i class="bi bi-receipt me-2"></i>Issue Citation</a>
                @endcan
                @can('create', App\Models\Payment::class)
                    <a href="{{ route('payments.create') }}" class="btn btn-outline-success text-start"><i class="bi bi-cash-stack me-2"></i>Process Payment</a>
                @endcan
                @can('create', App\Models\ClampingRecord::class)
                    <a href="{{ route('clamping.create') }}" class="btn btn-outline-danger text-start"><i class="bi bi-lock me-2"></i>Record Clamp</a>
                @endcan
                @can('viewAny', App\Models\Appeal::class)
                    <a href="{{ route('appeals.index') }}" class="btn btn-outline-secondary text-start"><i class="bi bi-chat-square-text me-2"></i>Review Appeals</a>
                @endcan
            </div>
        </div>
    </div>

    @if (!auth()->user()->isRole(\App\Enums\Role::Enforcer, \App\Enums\Role::ClampingOfficer))
    <div class="col-xl-4">
        <div class="card stat-card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>Zone Coverage</strong>
                <a href="{{ route('zones.index') }}" class="btn btn-sm btn-outline-primary" style="font-size:0.75rem;">Manage</a>
            </div>
            <div class="card-body p-0">
                @if ($zoneMapData->isEmpty())
                    <div class="text-center text-muted py-5 small">No active zones configured.</div>
                @else
                    <div id="dashboard-zone-map" class="dash-map"></div>
                @endif
            </div>
        </div>
    </div>
    @endif
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

    new Chart(document.getElementById('citationsChart'), {
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

    const revenueCanvas = document.getElementById('revenueChart');
    if (revenueCanvas) {
        new Chart(revenueCanvas, {
            type: 'line',
            data: {
                labels: revenueLabels,
                datasets: [{ label: 'Revenue (₱)', data: revenueData, borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,0.08)', fill: true, tension: 0.35 }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { callback: v => '₱' + Number(v).toLocaleString() } } }
            }
        });
    }

    new Chart(document.getElementById('appealsChart'), {
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

    if (window.__zonePicker?.initZoneViewer) {
        const zoneData = @json($zoneMapData);
        if (zoneData.length) {
            window.__zonePicker.initZoneViewer('dashboard-zone-map', {
                zones: zoneData, zoom: 10,
            });
        }
    }

    // Enforcer GPS toggle-based tracking
    const gpsToggle = document.getElementById('gps-toggle');
    const gpsStatusEl = document.getElementById('gps-status');
    const gpsControls = document.getElementById('gps-controls');
    const gpsUpdateNow = document.getElementById('gps-update-now');
    const gpsIntervalSelect = document.getElementById('gps-interval');
    let gpsPollTimer = null;

    if (gpsToggle && gpsStatusEl) {
        async function sendGPSOnce() {
            if (!navigator.geolocation) {
                gpsStatusEl.innerHTML = '<span class="badge bg-danger">Geolocation not supported</span>';
                return;
            }
            gpsStatusEl.innerHTML = '<span class="badge bg-info"><span class="spinner-border spinner-border-sm me-1"></span>Acquiring GPS...</span>';
            return new Promise((resolve) => {
                navigator.geolocation.getCurrentPosition(
                    async (pos) => {
                        const { latitude, longitude, accuracy } = pos.coords;
                        gpsStatusEl.innerHTML = '<span class="badge bg-info"><span class="spinner-border spinner-border-sm me-1"></span>Sending...</span>';
                        try {
                            const res = await fetch('{{ route("location.update") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                                },
                                body: JSON.stringify({ latitude, longitude, accuracy_m: Math.round(accuracy) })
                            });
                            if (res.ok) {
                                const sec = gpsToggle.checked ? Math.round(parseInt(gpsIntervalSelect.value) / 1000) : null;
                                gpsStatusEl.innerHTML = sec
                                    ? '<span class="badge bg-success">Active · updated every ' + sec + 's</span>'
                                    : '<span class="badge bg-success">Location updated ✓</span>';
                            } else {
                                let msg = 'Server error (' + res.status + ')';
                                try {
                                    const errBody = await res.text();
                                    try { const j = JSON.parse(errBody); msg = j.message || j.error || msg; } catch (_) { if (errBody) msg = errBody.substring(0, 80); }
                                } catch (_) {}
                                gpsStatusEl.innerHTML = '<span class="badge bg-danger">Failed: ' + msg + '</span>';
                            }
                        } catch (e) {
                            gpsStatusEl.innerHTML = '<span class="badge bg-danger">Network error</span>';
                        }
                        resolve();
                    },
                    (err) => {
                        const gpsErrors = { 1: 'GPS permission denied', 2: 'GPS position unavailable', 3: 'GPS request timed out' };
                        gpsStatusEl.innerHTML = '<span class="badge bg-danger">' + (gpsErrors[err.code] || 'GPS error') + '</span>';
                        resolve();
                    },
                    { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
                );
            });
        }

        function startGPSPolling() {
            const ms = parseInt(gpsIntervalSelect.value) || 5000;
            sendGPSOnce();
            gpsPollTimer = setInterval(sendGPSOnce, ms);
            const sec = Math.round(ms / 1000);
            gpsStatusEl.innerHTML = '<span class="badge bg-success">Active · updating every ' + sec + 's</span>';
        }

        function stopGPSPolling() {
            if (gpsPollTimer) { clearInterval(gpsPollTimer); gpsPollTimer = null; }
            gpsStatusEl.innerHTML = '<span class="badge bg-secondary">Tracking paused</span>';
        }

        gpsToggle.addEventListener('change', () => {
            if (gpsToggle.checked) {
                gpsControls.style.display = 'flex';
                startGPSPolling();
            } else {
                gpsControls.style.display = 'none';
                stopGPSPolling();
            }
        });

        gpsUpdateNow?.addEventListener('click', () => sendGPSOnce());

        gpsIntervalSelect?.addEventListener('change', () => {
            if (gpsToggle.checked) {
                if (gpsPollTimer) clearInterval(gpsPollTimer);
                const ms = parseInt(gpsIntervalSelect.value) || 5000;
                gpsPollTimer = setInterval(sendGPSOnce, ms);
                const sec = Math.round(ms / 1000);
                gpsStatusEl.innerHTML = '<span class="badge bg-success">Active · updating every ' + sec + 's</span>';
            }
        });
    }
});
</script>
@endpush
