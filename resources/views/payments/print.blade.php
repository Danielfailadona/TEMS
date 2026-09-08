<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Receipt #{{ $payment->receipt_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #111; font-size: 12px; background: #e5e7eb; }
        .ticket { width: 5in; height: 7in; margin: 16px auto; padding: 0.35in; background: #fff; position: relative; box-shadow: 0 4px 20px rgba(0,0,0,0.15); display: flex; flex-direction: column; page-break-inside: avoid; break-inside: avoid; }

        .header { text-align: center; border-bottom: 2px solid #111; padding-bottom: 10px; margin-bottom: 14px; flex-shrink: 0; }
        .header img { height: 52px; margin-bottom: 4px; }
        .header .name { font-size: 17px; font-weight: 700; letter-spacing: 0.02em; }
        .header .sub { font-size: 10px; letter-spacing: 0.12em; text-transform: uppercase; color: #444; }

        .title-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-shrink: 0; }
        .title-row h1 { font-size: 16px; }
        .status { font-size: 11px; font-weight: 700; padding: 3px 10px; border: 2px solid #111; border-radius: 999px; }

        .copy-banner { font-size: 11px; font-weight: 700; text-align: center; border: 2px dashed #111; padding: 4px; margin-bottom: 10px; flex-shrink: 0; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; flex-shrink: 0; }
        td { padding: 4px 0; vertical-align: top; }
        td.label { width: 40%; font-size: 10px; text-transform: uppercase; letter-spacing: 0.04em; color: #555; }
        td.value { font-weight: 600; font-size: 12px; text-align: right; }

        .qr { text-align: center; margin: 8px 0; flex-shrink: 0; }
        .qr img { max-width: 150px; height: auto; }
        .qr .hint { font-size: 9px; color: #555; margin-top: 4px; }

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

        .footer { margin-top: auto; padding-top: 8px; font-size: 9px; color: #333; flex-shrink: 0; }
        .footer-divider { border-top: 1px solid #111; margin: 8px 0; }
        .footer-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 8px; }
        .footer-col { text-align: center; }
        .footer-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #555; margin-bottom: 4px; }
        .officer-name { font-size: 11px; font-weight: 600; margin-bottom: 2px; }
        .officer-role { font-size: 9px; color: #444; margin-bottom: 2px; }
        .officer-id { font-size: 9px; color: #666; }
        .inquiry-text { font-size: 9px; color: #444; margin-bottom: 2px; }
        .inquiry-number { font-size: 10px; font-weight: 700; margin-bottom: 2px; }
        .inquiry-location { font-size: 9px; color: #444; }
        .footer-meta { text-align: center; border-top: 1px dashed #999; padding-top: 8px; }
        .meta-line { margin-bottom: 3px; }
        .system-notice { font-size: 8px; color: #888; font-style: italic; margin-top: 4px; }

        .print-btn { text-align: center; margin: 16px 0; }
        .print-btn button, .print-btn a {
            display: inline-block; padding: 10px 24px; font-size: 14px; cursor: pointer;
            border-radius: 8px; text-decoration: none; margin: 0 4px;
        }

        @media print {
            @page { size: 5in 7in; margin: 0; }
            body { background: #fff; }
            .ticket { width: 5in; height: 7in; margin: 0; padding: 0.35in; box-shadow: none; }
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
    @if ($payment->paid_at)
        <div class="stamp-paid">PAID</div>
    @endif

    <div class="header">
        <img src="{{ asset('images/transpo_enfo_orig.png') }}" alt="TEMs">
        <div class="name">{{ config('itevcms.app_name') }}</div>
        <div class="sub">Transportation Enforcement Management System</div>
    </div>

    <div class="title-row">
        <h1>Payment Receipt <span style="color:#555;">#{{ $payment->receipt_number }}</span></h1>
        <span class="status">{{ $payment->paid_at ? 'PAID' : 'PENDING' }}</span>
    </div>

    <div class="copy-banner">OFFICIAL COPY</div>

    <table>
        <tr><td class="label">Receipt #</td><td class="value">{{ $payment->receipt_number }}</td></tr>
        <tr><td class="label">Citation #</td><td class="value">{{ $payment->citation->citation_number }}</td></tr>
        <tr><td class="label">Vehicle</td><td class="value">{{ $payment->citation->vehicle_plate }}</td></tr>
        <tr><td class="label">Violation</td><td class="value">{{ $payment->citation->violationType->name }}</td></tr>
        <tr><td class="label">Penalty Amount</td><td class="value">₱{{ number_format($payment->citation->penalty_amount, 2) }}</td></tr>
        <tr><td class="label">Amount Paid</td><td class="value">₱{{ number_format($payment->amount, 2) }}</td></tr>
        <tr><td class="label">Payment Method</td><td class="value">{{ $payment->isOnlinePayment() ? ucfirst($payment->online_payment_method ?? 'Online Payment') : $payment->payment_method->label() }}</td></tr>
        @if ($payment->reference_number)
            <tr><td class="label">Reference</td><td class="value">{{ $payment->reference_number }}</td></tr>
        @endif
        @if ($payment->paymongo_checkout_id)
            <tr><td class="label">Checkout ID</td><td class="value"><code class="small">{{ $payment->paymongo_checkout_id }}</code></td></tr>
        @endif
        <tr><td class="label">Cashier</td><td class="value">{{ $payment->cashier->name ?? 'Online Payment' }}</td></tr>
        <tr><td class="label">Date Paid</td><td class="value">{{ $payment->paid_at?->format('F d, Y h:i A') ?? 'Pending' }}</td></tr>
    </table>

    @if (! $payment->paid_at)
        <div class="qr">
            {!! $payment->citation->getQRCodeSvg(150) !!}
            <div class="hint">Scan to view citation details and pay online</div>
        </div>
    @endif

    <div class="footer">
        @include('partials.ticket-print-footer', [
            'officer' => $payment->citation->enforcer,
            'docType' => 'Receipt',
            'docNumber' => $payment->receipt_number,
        ])
    </div>
</div>
</body>
</html>