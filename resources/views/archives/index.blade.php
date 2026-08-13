@extends('layouts.app')

@section('title', 'Archives')

@push('styles')
<style>
.archive-card {
    border:1px solid var(--itevcms-border); border-radius:0.75rem;
    background:var(--itevcms-card); overflow:hidden;
    transition:box-shadow 0.15s ease, border-color 0.15s ease;
    cursor:pointer;
}
.archive-card:hover { box-shadow:0 4px 16px rgba(0,0,0,0.06); border-color:#cbd5e1; }
.archive-card-header {
    display:flex; align-items:center; gap:0.75rem;
    padding:0.85rem 1rem;
}
.archive-card-body {
    max-height:0; overflow:hidden;
    transition:max-height 0.25s ease, padding 0.25s ease;
    padding:0 1rem;
    border-top:1px solid transparent;
}
.archive-card.is-expanded .archive-card-body {
    max-height:800px; padding:0.75rem 1rem;
    border-top-color:var(--itevcms-border);
}
.archive-type-badge {
    font-size:0.6rem; font-weight:700; text-transform:uppercase;
    padding:0.2rem 0.55rem; border-radius:0.3rem; letter-spacing:0.03em;
    flex-shrink:0;
}
.archive-detail-grid {
    display:grid; grid-template-columns:auto 1fr; gap:0.35rem 1rem;
    font-size:0.82rem;
}
.archive-detail-grid dt { color:var(--itevcms-text-muted); font-weight:500; white-space:nowrap; }
.archive-detail-grid dd { margin:0; word-break:break-word; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center gap-2 mb-4">
    <p class="text-muted small mb-0">Resolved and completed records across the system.</p>
    <form method="GET" class="d-flex align-items-center gap-2">
        <select name="type" class="form-select form-select-sm" style="min-width:140px;" onchange="this.form.submit()">
            <option value="">All Types</option>
            @foreach ($types as $type)
                <option value="App\Models\{{ $type }}" @selected(request('type') === "App\Models\\{$type}")>{{ $type }}</option>
            @endforeach
        </select>
        @if (request('type'))
            <a href="{{ route('archives.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
        @endif
    </form>
</div>

@php
function archiveSummary($archive) {
    $snap = $archive->snapshot ?? [];
    $type = class_basename($archive->archivable_type);
    $id = $snap['id'] ?? $archive->archivable_id;
    $officer = $snap['officer']['name'] ?? $snap['enforcer']['name'] ?? $archive->archivedBy?->name;
    $status = $snap['status'] ?? null;

    switch ($type) {
        case 'Citation':
            return [
                'icon' => 'bi-receipt',
                'color' => '#2563eb',
                'badge' => 'Citation',
                'title' => $snap['citation_number'] ?? "CIT-{$id}",
                'subtitle' => $snap['driver_name'] ?? '',
                'meta' => 'Vehicle: '.($snap['vehicle_plate'] ?? 'N/A'),
                'officer' => $officer ?? ($snap['issued_by_name'] ?? 'System'),
                'status' => $status,
            ];
        case 'Appeal':
            return [
                'icon' => 'bi-chat-square-text',
                'color' => '#7c3aed',
                'badge' => 'Appeal',
                'title' => "Appeal #{$id}",
                'subtitle' => $snap['reason'] ?? '',
                'meta' => 'Citation: '.($snap['citation_number'] ?? '#'.$snap['citation_id']),
                'officer' => $officer ?? $archive->archivedBy?->name ?? 'System',
                'status' => $status,
            ];
        case 'ClampingRecord':
            return [
                'icon' => 'bi-lock',
                'color' => '#dc2626',
                'badge' => 'Clamping',
                'title' => $snap['notice_number'] ?? "CLP-{$id}",
                'subtitle' => $snap['vehicle_plate'] ?? '',
                'meta' => $snap['location'] ?? '',
                'officer' => $officer ?? ($snap['clamped_by_name'] ?? 'System'),
                'status' => $status,
            ];
        case 'ClampingRequest':
            return [
                'icon' => 'bi-geo-alt',
                'color' => '#0891b2',
                'badge' => 'Request',
                'title' => $snap['requester_name'] ?? "Request #{$id}",
                'subtitle' => $snap['vehicle_plate'] ?? '',
                'meta' => $snap['location_address'] ?? '',
                'officer' => $archive->archivedBy?->name ?? ($snap['processed_by_name'] ?? 'System'),
                'status' => $status,
            ];
        default:
            return [
                'icon' => 'bi-archive',
                'color' => '#6b7280',
                'badge' => $type,
                'title' => "Record #{$id}",
                'subtitle' => '',
                'meta' => '',
                'officer' => $archive->archivedBy?->name ?? 'System',
                'status' => $status,
            ];
    }
}

function archiveDetails($archive) {
    $snap = $archive->snapshot ?? [];
    $type = class_basename($archive->archivable_type);
    $details = [];

    switch ($type) {
        case 'Citation':
            $fields = [
                'Citation #' => $snap['citation_number'] ?? null,
                'Driver' => $snap['driver_name'] ?? null,
                'License' => $snap['driver_license'] ?? null,
                'Vehicle' => $snap['vehicle_plate'] ?? null,
                'Make/Model' => trim(($snap['vehicle_make']??'').' '.($snap['vehicle_model']??'')) ?: null,
                'Violation' => $snap['violation_type']['name'] ?? $snap['violation_type_name'] ?? null,
                'Penalty' => isset($snap['penalty_amount']) ? '₱'.number_format($snap['penalty_amount'], 2) : null,
                'Location' => $snap['location'] ?? null,
                'Issued At' => isset($snap['issued_at']) ? \Carbon\Carbon::parse($snap['issued_at'])->format('M d, Y H:i') : null,
                'Status' => $snap['status'] ?? null,
            ];
            foreach ($fields as $label => $val) { if ($val) $details[$label] = $val; }
            break;
        case 'Appeal':
            $fields = [
                'Citation #' => $snap['citation_number'] ?? '#'.$snap['citation_id'],
                'Reason' => $snap['reason'] ?? null,
                'Decision' => $snap['decision_notes'] ?? null,
                'Submitted' => isset($snap['submitted_at']) ? \Carbon\Carbon::parse($snap['submitted_at'])->format('M d, Y H:i') : null,
                'Reviewed' => isset($snap['reviewed_at']) ? \Carbon\Carbon::parse($snap['reviewed_at'])->format('M d, Y H:i') : null,
                'Status' => $snap['status'] ?? null,
            ];
            foreach ($fields as $label => $val) { if ($val) $details[$label] = $val; }
            break;
        case 'ClampingRecord':
            $fields = [
                'Notice #' => $snap['notice_number'] ?? null,
                'Vehicle' => $snap['vehicle_plate'] ?? null,
                'Location' => $snap['location'] ?? null,
                'Clamped At' => isset($snap['clamped_at']) ? \Carbon\Carbon::parse($snap['clamped_at'])->format('M d, Y H:i') : null,
                'Status' => $snap['status'] ?? null,
            ];
            foreach ($fields as $label => $val) { if ($val) $details[$label] = $val; }
            break;
        case 'ClampingRequest':
            $fields = [
                'Requester' => $snap['requester_name'] ?? null,
                'Phone' => $snap['requester_phone'] ?? null,
                'Vehicle' => $snap['vehicle_plate'] ?? null,
                'Location' => $snap['location_address'] ?? null,
                'Processed At' => isset($snap['processed_at']) ? \Carbon\Carbon::parse($snap['processed_at'])->format('M d, Y H:i') : null,
                'Status' => $snap['status'] ?? null,
            ];
            foreach ($fields as $label => $val) { if ($val) $details[$label] = $val; }
            break;
        default:
            foreach ($snap as $k => $v) {
                if (is_scalar($v) || is_null($v)) {
                    $details[ucwords(str_replace('_', ' ', $k))] = $v ?? '—';
                }
            }
    }

    return $details;
}
@endphp

@if ($archives->isEmpty())
    <div class="text-center py-5 text-muted">
        <i class="bi bi-archive" style="font-size:2.5rem;display:block;margin-bottom:0.75rem;opacity:0.3;"></i>
        <span class="fw-semibold">No archived records</span>
        <div class="small">Resolved records will appear here automatically.</div>
    </div>
@else
    <div class="row g-3">
        @foreach ($archives as $archive)
            @php $sum = archiveSummary($archive); $det = archiveDetails($archive); @endphp
            <div class="col-md-6 col-xl-4">
                <div class="archive-card" onclick="toggleArchive(this)" role="button">
                    <div class="archive-card-header">
                        <span class="archive-type-badge" style="background:{{ $sum['color'] }}15;color:{{ $sum['color'] }};">
                            <i class="bi {{ $sum['icon'] }} me-1"></i>{{ $sum['badge'] }}
                        </span>
                        <div class="flex-grow-1 min-width-0">
                            <div class="fw-semibold small text-truncate">{{ $sum['title'] }}</div>
                            <div class="text-muted" style="font-size:0.7rem;">
                                {{ $sum['officer'] }} · {{ $archive->archived_at->format('M d, Y') }}
                            </div>
                        </div>
                        <i class="bi bi-chevron-down text-muted" style="font-size:0.7rem;transition:transform 0.2s;"></i>
                    </div>

                    <div class="archive-card-body">
                        <dl class="archive-detail-grid">
                            @foreach ($det as $label => $val)
                                <dt>{{ $label }}</dt>
                                <dd>{{ $val }}</dd>
                            @endforeach
                            <dt>Archived By</dt>
                            <dd>{{ $archive->archivedBy?->name ?? 'System' }}</dd>
                            @if ($archive->reason)
                                <dt>Reason</dt>
                                <dd>{{ $archive->reason }}</dd>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if ($archives->hasPages())
        <div class="mt-4">{{ $archives->links() }}</div>
    @endif
@endif
@endsection

@push('scripts')
<script>
function toggleArchive(el) {
    el.classList.toggle('is-expanded');
    const chevron = el.querySelector('.bi-chevron-down');
    if (chevron) {
        chevron.style.transform = el.classList.contains('is-expanded') ? 'rotate(180deg)' : '';
    }
}
</script>
@endpush
