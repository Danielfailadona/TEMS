<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Citation #{{ $citation->citation_number }}</title>
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

        .qr { text-align: center; margin: 4px 0; flex-shrink: 0; }
        .qr img { max-width: 100px; height: auto; }
        .qr .hint { font-size: 8px; color: #555; margin-top: 2px; }

        .stamp-paid {
            position: absolute;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-14deg);
            font-size: 34px;
            font-weight: 800;
            letter-spacing: 0.08em;
            color: rgba(22, 163, 74, 0.35);
            border: 5px solid rgba(22, 163, 74, 0.35);
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
    <a href="javascript:history.back()" style="border:1px solid #999; color:#333;">Back</a>
</div>

<div class="ticket">
    @if ($citation->isPaid())
        <div class="stamp-paid">PAID</div>
    @endif

    <div class="header">
        <img src="{{ asset('images/transpo_enfo_orig.png') }}" alt="TEMs">
        <div class="name">{{ config('itevcms.app_name') }}</div>
        <div class="sub">Transportation Enforcement Management System</div>
    </div>

    <div class="title-row">
        <h1>Citation Ticket <span style="color:#555;">#{{ $citation->citation_number }}</span></h1>
        <span class="status">{{ $citation->status->label() }}</span>
    </div>

    @if (! empty($isEnforcerCopy))
        <div class="copy-banner">OFFICIAL COPY</div>
    @else
        <div class="copy-banner">VIOLATOR COPY</div>
    @endif

    <table>
        <tr><td class="label">Violation</td><td class="value">{{ $citation->violationType->name }}</td></tr>
        <tr><td class="label">Penalty Amount</td><td class="value">₱{{ number_format($citation->penalty_amount, 2) }}</td></tr>
        <tr><td class="label">Plate Number</td><td class="value">{{ $citation->vehicle_plate }}</td></tr>
        <tr><td class="label">Vehicle</td><td class="value">{{ $citation->vehicle_make }} {{ $citation->vehicle_model }} {{ $citation->vehicle_type ? '('.$citation->vehicle_type.')' : '' }}</td></tr>
        <tr><td class="label">Color</td><td class="value">{{ $citation->vehicle_color ?? '&mdash;' }}</td></tr>
        <tr><td class="label">Driver</td><td class="value">{{ $citation->driver_name ?? '&mdash;' }}</td></tr>
        <tr><td class="label">Date Issued</td><td class="value">{{ $citation->issued_at?->format('F d, Y h:i A') }}</td></tr>
        <tr><td class="label">Due Date</td><td class="value">{{ $citation->due_date?->format('F d, Y') }}</td></tr>
        <tr><td class="label">Location</td><td class="value">{{ $citation->location ?? '&mdash;' }}</td></tr>
        <tr><td class="label">Issued By</td><td class="value">{{ $citation->enforcer->name ?? '&mdash;' }}</td></tr>
        @if ($citation->notes)
            <tr><td class="label">Notes</td><td class="value">{{ $citation->notes }}</td></tr>
        @endif
    </table>

    <table>
        @if ($citation->payment)
            <tr><td class="label">Receipt #</td><td class="value">{{ $citation->payment->receipt_number }}</td></tr>
            <tr><td class="label">Amount Paid</td><td class="value">₱{{ number_format($citation->payment->amount, 2) }}</td></tr>
            <tr><td class="label">Paid On</td><td class="value">{{ $citation->payment->paid_at?->format('F d, Y h:i A') ?? 'Pending' }}</td></tr>
            <tr><td class="label">Method</td><td class="value">{{ $citation->payment->online_payment_method ? ucfirst($citation->payment->online_payment_method) : $citation->payment->payment_method->label() }}</td></tr>
        @else
            <tr><td class="label">Payment Status</td><td class="value">Pending</td></tr>
        @endif
    </table>

    @if (! $citation->isPaid())
        <div class="qr">
            {!! $citation->getQRCodeSvg(100) !!}
            <div class="hint">Scan to view citation details and pay online</div>
        </div>
    @endif

    <div class="footer">
        @include('partials.ticket-print-footer', [
            'officer' => $citation->enforcer,
            'docType' => 'Citation',
            'docNumber' => $citation->citation_number,
        ])
    </div>
</div>
</body>
</html>