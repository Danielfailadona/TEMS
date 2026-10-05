<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Release Order #{{ $impounding->release->release_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #111; font-size: 11px; background: #e5e7eb; }
        .ticket { width: 5in; height: 7in; margin: 16px auto; padding: 0.3in; background: #fff; position: relative; box-shadow: 0 4px 20px rgba(0,0,0,0.15); display: flex; flex-direction: column; page-break-inside: avoid; break-inside: avoid; overflow: hidden; }

        .header { text-align: center; border-bottom: 2px solid #111; padding-bottom: 6px; margin-bottom: 8px; flex-shrink: 0; }
        .header img { height: 40px; margin-bottom: 3px; }
        .header .name { font-size: 15px; font-weight: 700; letter-spacing: 0.02em; }
        .header .sub { font-size: 9px; letter-spacing: 0.1em; text-transform: uppercase; color: #444; }

        .title-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; flex-shrink: 0; }
        .title-row h1 { font-size: 14px; }
        .status { font-size: 10px; font-weight: 700; padding: 2px 8px; border: 2px solid #111; border-radius: 999px; }

        .copy-banner { font-size: 10px; font-weight: 700; text-align: center; border: 2px dashed #111; padding: 3px; margin-bottom: 6px; flex-shrink: 0; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; flex-shrink: 0; }
        td { padding: 2px 0; vertical-align: top; }
        td.label { width: 40%; font-size: 9px; text-transform: uppercase; letter-spacing: 0.04em; color: #555; }
        td.value { font-weight: 600; font-size: 11px; text-align: right; }
        td.value code { font-size: 9px; }

        .qr { text-align: center; margin: 4px 0; flex-shrink: 0; }
        .qr img { max-width: 100px; height: auto; }
        .qr .hint { font-size: 8px; color: #555; margin-top: 2px; }

        .stamp-released {
            position: absolute;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-14deg);
            font-size: 34px;
            font-weight: 800;
            letter-spacing: 0.08em;
            color: rgba(34, 197, 94, 0.35);
            border: 5px solid rgba(34, 197, 94, 0.35);
            border-radius: 12px;
            padding: 6px 20px;
            pointer-events: none;
            z-index: 1;
        }

        .footer { margin-top: auto; padding-top: 4px; font-size: 8px; color: #333; flex-shrink: 0; }
        .footer-divider { border-top: 1px solid #111; margin: 4px 0; }
        .footer-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 4px; }
        .footer-col { text-align: center; }
        .footer-label { font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #555; margin-bottom: 2px; }
        .officer-name { font-size: 10px; font-weight: 600; margin-bottom: 1px; }
        .officer-role { font-size: 8px; color: #444; margin-bottom: 1px; }
        .officer-id { font-size: 8px; color: #666; }
        .inquiry-text { font-size: 8px; color: #444; margin-bottom: 1px; }
        .inquiry-number { font-size: 9px; font-weight: 700; margin-bottom: 1px; }
        .inquiry-location { font-size: 8px; color: #444; }
        .footer-meta { text-align: center; border-top: 1px dashed #999; padding-top: 4px; }
        .meta-line { margin-bottom: 2px; }
        .system-notice { font-size: 7px; color: #888; font-style: italic; margin-top: 2px; }

        .print-btn { text-align: center; margin: 16px 0; }
        .print-btn button, .print-btn a {
            display: inline-block; padding: 10px 24px; font-size: 14px; cursor: pointer;
            border-radius: 8px; text-decoration: none; margin: 0 4px;
        }

        @media print {
            @page { size: 5in 7in; margin: 0; }
            body { background: #fff; }
            .ticket { width: 5in; height: 7in; margin: 0; padding: 0.3in; box-shadow: none; }
            .print-btn { display: none !important; }
        }
    </style>
</head>
<body>
<div class="print-btn">
    <button onclick="window.print()" style="border:1px solid #2563eb; background:#2563eb; color:#fff;">
        <b>🖨 Print / Save as PDF</b>
    </button>
</div>

<div class="ticket">
    @if ($impounding->status === App\Enums\ImpoundingStatus::Released)
        <div class="stamp-released">RELEASED</div>
    @endif

    <div class="header">
        <img src="{{ asset('images/transpo_enfo_orig.png') }}" alt="TEMs">
        <div class="name">{{ config('itevcms.app_name') }}</div>
        <div class="sub">Traffic Enforcement Management System</div>
    </div>

    <div class="title-row">
        <h1>Release Order <span style="color:#555;">#{{ $impounding->release->release_number }}</span></h1>
        <span class="status">{{ $impounding->status === App\Enums\ImpoundingStatus::Released ? 'RELEASED' : 'NOT RELEASED' }}</span>
    </div>

    <div class="copy-banner">OFFICIAL COPY</div>

    <table>
        <tr><td class="label">Release #</td><td class="value">{{ $impounding->release->release_number }}</td></tr>
        <tr><td class="label">Notice #</td><td class="value">{{ $impounding->notice_number }}</td></tr>
        <tr><td class="label">Vehicle Plate</td><td class="value">{{ $impounding->vehicle_plate }}</td></tr>
        @if ($impounding->citation)
            <tr><td class="label">Citation #</td><td class="value">{{ $impounding->citation->citation_number }}</td></tr>
            <tr><td class="label">Violation</td><td class="value">{{ $impounding->citation->violationType->name }}</td></tr>
            <tr><td class="label">Penalty Amount</td><td class="value">₱{{ number_format($impounding->citation->penalty_amount, 2) }}</td></tr>
        @endif
        @if ($impounding->clampingRecord)
            <tr><td class="label">Clamping Notice #</td><td class="value">{{ $impounding->clampingRecord->notice_number }}</td></tr>
        @endif
        <tr><td class="label">Impounded At</td><td class="value">{{ $impounding->impounded_at->format('F d, Y h:i A') }}</td></tr>
        <tr><td class="label">Impounding Officer</td><td class="value">{{ $impounding->officer->name }}</td></tr>
        <tr><td class="label">Impound Location</td><td class="value">{{ $impounding->location ?? '—' }}</td></tr>

        <tr><td class="label">Towing Fee</td><td class="value">₱{{ number_format($impounding->towing_fee, 2) }}</td></tr>
        <tr><td class="label">Storage Fee/Day</td><td class="value">₱{{ number_format($impounding->storage_fee_per_day, 2) }}</td></tr>
        <tr><td class="label">Storage Days</td><td class="value">{{ $impounding->getStorageDays() }}</td></tr>
        <tr><td class="label">Admin Fee</td><td class="value">₱{{ number_format($impounding->admin_fee, 2) }}</td></tr>
        <tr><td class="label"><strong>Total Fees</strong></td><td class="value"><strong>₱{{ number_format($impounding->getTotalFees(), 2) }}</strong></td></tr>

        @php($ownPayment = $impounding->payments->firstWhere('paid_at'))
        @if ($ownPayment)
            <tr><td class="label">Payment Paid</td><td class="value">Yes (₱{{ number_format($ownPayment->amount, 2) }})</td></tr>
            <tr><td class="label">Receipt #</td><td class="value">{{ $ownPayment->receipt_number }}</td></tr>
        @elseif ($impounding->citation?->payment && $impounding->citation->paid_at)
            <tr><td class="label">Citation Paid</td><td class="value">Yes (₱{{ number_format($impounding->citation->payment->amount, 2) }})</td></tr>
            <tr><td class="label">Citation Receipt</td><td class="value">{{ $impounding->citation->payment->receipt_number }}</td></tr>
        @endif

        <tr><td class="label">Grace Until</td><td class="value">{{ $impounding->grace_until?->format('F d, Y H:i') ?? '—' }}</td></tr>
        <tr><td class="label">Released At</td><td class="value">{{ $impounding->release?->released_at?->format('F d, Y h:i A') ?? 'Not yet released' }}</td></tr>
        <tr><td class="label">Released By</td><td class="value">{{ $impounding->release->releasedBy->name ?? '—' }}</td></tr>
    </table>

    @if ($impounding->release && $impounding->release->notes)
        <div style="margin-top: 8px; padding: 8px; background: #f8fafc; border-radius: 4px; font-size: 10px;">
            <strong>Release Notes:</strong> {{ $impounding->release->notes }}
        </div>
    @endif

    <div style="margin-top: 12px; padding: 8px; background: #f0fdf4; border: 2px solid #22c55e; border-radius: 6px; text-align: center; font-size: 11px; font-weight: 700; color: #166534;">
        THIS VEHICLE IS HEREBY AUTHORIZED FOR RELEASE
    </div>

    <div class="footer">
        @include('partials.ticket-print-footer', [
            'officer' => $impounding->release?->releasedBy ?? $impounding->officer,
            'docType' => 'Release Order',
            'docNumber' => $impounding->release->release_number ?? $impounding->notice_number,
        ])
    </div>
</div>
</body>
</html>