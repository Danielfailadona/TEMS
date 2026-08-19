<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive Record — {{ $type }} #{{ $archive->id }}</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color:#1e293b; background:#fff; padding:2rem; }
        .doc-header { text-align:center; border-bottom:3px solid #1e293b; padding-bottom:1rem; margin-bottom:1.5rem; }
        .doc-header h1 { font-size:1.4rem; letter-spacing:0.05em; text-transform:uppercase; color:#0f172a; }
        .doc-header .subtitle { font-size:0.75rem; color:#64748b; margin-top:0.25rem; }
        .type-badge { display:inline-flex; align-items:center; gap:0.4rem; font-size:0.75rem; font-weight:700; text-transform:uppercase; letter-spacing:0.03em; padding:0.3rem 0.75rem; border-radius:0.35rem; color:#fff; margin-bottom:1rem; }
        .doc-section { margin-bottom:1.5rem; }
        .doc-section h2 { font-size:0.85rem; text-transform:uppercase; letter-spacing:0.04em; color:#64748b; border-bottom:1px solid #e2e8f0; padding-bottom:0.3rem; margin-bottom:0.75rem; }
        .detail-table { width:100%; border-collapse:collapse; font-size:0.82rem; }
        .detail-table tr { border-bottom:1px solid #f1f5f9; }
        .detail-table td { padding:0.45rem 0.6rem; vertical-align:top; }
        .detail-table td:first-child { font-weight:600; color:#64748b; width:35%; white-space:nowrap; }
        .detail-table td:last-child { color:#0f172a; }
        .footer-section { border-top:2px solid #1e293b; padding-top:1rem; margin-top:1.5rem; }
        .footer-section .meta { font-size:0.78rem; color:#64748b; }
        .footer-section .meta span { display:block; margin-bottom:0.2rem; }
        .print-actions { text-align:center; margin-bottom:1.5rem; }
        .print-actions button { padding:0.5rem 1.5rem; font-size:0.85rem; cursor:pointer; border:1px solid #cbd5e1; border-radius:0.4rem; background:#f8fafc; color:#334155; }
        .print-actions button:hover { background:#e2e8f0; }
        @media print {
            .print-actions { display:none !important; }
            body { padding:1rem; }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button onclick="window.print()"><i class="bi bi-printer"></i> Print This Page</button>
    </div>

    <div class="doc-header">
        <h1>TEMS Official Archive Record</h1>
        <div class="subtitle">Transportation Enforcement Management System — {{ config('app.name', 'TEMS') }}</div>
    </div>

    @php
        $snap = $archive->snapshot ?? [];
        $id = $snap['id'] ?? $archive->archivable_id;

        $badgeConfig = match($type) {
            'Citation' => ['label' => 'Citation', 'color' => '#2563eb', 'icon' => 'bi-receipt'],
            'Appeal' => ['label' => 'Appeal', 'color' => '#7c3aed', 'icon' => 'bi-chat-square-text'],
            'ClampingRecord' => ['label' => 'Clamping Record', 'color' => '#dc2626', 'icon' => 'bi-lock'],
            'ClampingRequest' => ['label' => 'Clamping Request', 'color' => '#0891b2', 'icon' => 'bi-geo-alt'],
            default => ['label' => $type, 'color' => '#6b7280', 'icon' => 'bi-archive'],
        };
    @endphp

    <div class="type-badge" style="background:{{ $badgeConfig['color'] }};">
        <i class="bi {{ $badgeConfig['icon'] }}"></i> {{ $badgeConfig['label'] }} Record #{{ $id }}
    </div>

    {{-- Citation --}}
    @if ($type === 'Citation')
        <div class="doc-section">
            <h2>Citation Information</h2>
            <table class="detail-table">
                <tr><td>Citation Number</td><td>{{ $snap['citation_number'] ?? "CIT-{$id}" }}</td></tr>
                <tr><td>Status</td><td>{{ strtoupper($snap['status'] ?? 'N/A') }}</td></tr>
                <tr><td>Violation Type</td><td>{{ $snap['violation_type']['name'] ?? $snap['violation_type_name'] ?? 'N/A' }}</td></tr>
                <tr><td>Penalty Amount</td><td>{{ isset($snap['penalty_amount']) ? '₱'.number_format($snap['penalty_amount'], 2) : 'N/A' }}</td></tr>
                <tr><td>Location</td><td>{{ $snap['location'] ?? 'N/A' }}</td></tr>
                <tr><td>Issued At</td><td>{{ isset($snap['issued_at']) ? \Carbon\Carbon::parse($snap['issued_at'])->format('M d, Y h:i A') : 'N/A' }}</td></tr>
            </table>
        </div>

        <div class="doc-section">
            <h2>Vehicle & Driver</h2>
            <table class="detail-table">
                <tr><td>Vehicle Plate</td><td>{{ $snap['vehicle_plate'] ?? 'N/A' }}</td></tr>
                <tr><td>Make / Model</td><td>{{ trim(($snap['vehicle_make'] ?? '').' '.($snap['vehicle_model'] ?? '')) ?: 'N/A' }}</td></tr>
                <tr><td>Vehicle Type</td><td>{{ $snap['vehicle_type'] ?? 'N/A' }}</td></tr>
                <tr><td>Color</td><td>{{ $snap['vehicle_color'] ?? 'N/A' }}</td></tr>
                <tr><td>Driver Name</td><td>{{ $snap['driver_name'] ?? 'N/A' }}</td></tr>
                <tr><td>License Number</td><td>{{ $snap['driver_license'] ?? 'N/A' }}</td></tr>
            </table>
        </div>

        <div class="doc-section">
            <h2>Issuing Officer</h2>
            <table class="detail-table">
                <tr><td>Issued By</td><td>{{ $snap['officer']['name'] ?? $snap['enforcer']['name'] ?? $snap['issued_by_name'] ?? 'System' }}</td></tr>
                @if ($snap['notes'] ?? null)
                    <tr><td>Notes</td><td>{{ $snap['notes'] }}</td></tr>
                @endif
            </table>
        </div>

    {{-- Appeal --}}
    @elseif ($type === 'Appeal')
        <div class="doc-section">
            <h2>Appeal Information</h2>
            <table class="detail-table">
                <tr><td>Appeal Number</td><td>APEAL-{{ $id }}</td></tr>
                <tr><td>Citation Number</td><td>{{ $snap['citation_number'] ?? '#'.$snap['citation_id'] ?? 'N/A' }}</td></tr>
                <tr><td>Status</td><td>{{ strtoupper($snap['status'] ?? 'N/A') }}</td></tr>
                <tr><td>Reason</td><td>{{ $snap['reason'] ?? 'N/A' }}</td></tr>
                <tr><td>Submitted At</td><td>{{ isset($snap['submitted_at']) ? \Carbon\Carbon::parse($snap['submitted_at'])->format('M d, Y h:i A') : 'N/A' }}</td></tr>
            </table>
        </div>

        <div class="doc-section">
            <h2>Review Details</h2>
            <table class="detail-table">
                <tr><td>Reviewed By</td><td>{{ $snap['reviewed_by_name'] ?? $snap['officer']['name'] ?? 'N/A' }}</td></tr>
                <tr><td>Reviewed At</td><td>{{ isset($snap['reviewed_at']) ? \Carbon\Carbon::parse($snap['reviewed_at'])->format('M d, Y h:i A') : 'N/A' }}</td></tr>
                <tr><td>Decision Notes</td><td>{{ $snap['decision_notes'] ?? 'N/A' }}</td></tr>
            </table>
        </div>

    {{-- ClampingRecord --}}
    @elseif ($type === 'ClampingRecord')
        <div class="doc-section">
            <h2>Clamping Record</h2>
            <table class="detail-table">
                <tr><td>Notice Number</td><td>{{ $snap['notice_number'] ?? "CLP-{$id}" }}</td></tr>
                <tr><td>Vehicle Plate</td><td>{{ $snap['vehicle_plate'] ?? 'N/A' }}</td></tr>
                <tr><td>Location</td><td>{{ $snap['location'] ?? 'N/A' }}</td></tr>
                <tr><td>Status</td><td>{{ strtoupper($snap['status'] ?? 'N/A') }}</td></tr>
                <tr><td>Clamped At</td><td>{{ isset($snap['clamped_at']) ? \Carbon\Carbon::parse($snap['clamped_at'])->format('M d, Y h:i A') : 'N/A' }}</td></tr>
            </table>
        </div>

        <div class="doc-section">
            <h2>Officer Details</h2>
            <table class="detail-table">
                <tr><td>Clamped By</td><td>{{ $snap['officer']['name'] ?? $snap['clamped_by_name'] ?? 'System' }}</td></tr>
                @if ($snap['notes'] ?? null)
                    <tr><td>Notes</td><td>{{ $snap['notes'] }}</td></tr>
                @endif
            </table>
        </div>

    {{-- ClampingRequest --}}
    @elseif ($type === 'ClampingRequest')
        <div class="doc-section">
            <h2>Request Information</h2>
            <table class="detail-table">
                <tr><td>Requester Name</td><td>{{ $snap['requester_name'] ?? 'N/A' }}</td></tr>
                <tr><td>Phone</td><td>{{ $snap['requester_phone'] ?? 'N/A' }}</td></tr>
                <tr><td>Email</td><td>{{ $snap['requester_email'] ?? 'N/A' }}</td></tr>
                <tr><td>Vehicle Plate</td><td>{{ $snap['vehicle_plate'] ?? 'N/A' }}</td></tr>
                <tr><td>Vehicle Description</td><td>{{ $snap['vehicle_description'] ?? 'N/A' }}</td></tr>
                <tr><td>Location</td><td>{{ $snap['location_address'] ?? 'N/A' }}</td></tr>
                <tr><td>Status</td><td>{{ strtoupper($snap['status'] ?? 'N/A') }}</td></tr>
            </table>
        </div>

        <div class="doc-section">
            <h2>Processing Details</h2>
            <table class="detail-table">
                <tr><td>Processed By</td><td>{{ $snap['processed_by_name'] ?? 'N/A' }}</td></tr>
                <tr><td>Processed At</td><td>{{ isset($snap['processed_at']) ? \Carbon\Carbon::parse($snap['processed_at'])->format('M d, Y h:i A') : 'N/A' }}</td></tr>
                @if ($snap['additional_notes'] ?? null)
                    <tr><td>Additional Notes</td><td>{{ $snap['additional_notes'] }}</td></tr>
                @endif
            </table>
        </div>

    {{-- Default / Unknown --}}
    @else
        <div class="doc-section">
            <h2>Record Information</h2>
            <table class="detail-table">
                <tr><td>Record ID</td><td>{{ $archive->archivable_id }}</td></tr>
                <tr><td>Status</td><td>{{ strtoupper($snap['status'] ?? 'N/A') }}</td></tr>
            </table>
        </div>
    @endif

    <div class="footer-section">
        <div class="meta">
            <span><strong>Archived By:</strong> {{ $archive->archivedBy?->name ?? 'System' }}</span>
            <span><strong>Archive Date:</strong> {{ $archive->archived_at->format('M d, Y h:i A') }}</span>
            @if ($archive->reason)
                <span><strong>Reason:</strong> {{ $archive->reason }}</span>
            @endif
            <span><strong>Archive ID:</strong> {{ $archive->id }}</span>
        </div>
    </div>
</body>
</html>
