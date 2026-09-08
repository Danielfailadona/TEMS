<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Citation #{{ $citation->citation_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #111; font-size: 12px; background: #e5e7eb; }
        .ticket { width: 5in; min-height: 7in; margin: 16px auto; padding: 0.35in; background: #fff; position: relative; box-shadow: 0 4px 20px rgba(0,0,0,0.15); }

        .header { text-align: center; border-bottom: 2px solid #111; padding-bottom: 10px; margin-bottom: 14px; }
        .header img { height: 52px; margin-bottom: 4px; }
        .header .name { font-size: 17px; font-weight: 700; letter-spacing: 0.02em; }
        .header .sub { font-size: 10px; letter-spacing: 0.12em; text-transform: uppercase; color: #444; }

        .title-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .title-row h1 { font-size: 16px; }
        .status { font-size: 11px; font-weight: 700; padding: 3px 10px; border: 2px solid #111; border-radius: 999px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        td { padding: 4px 0; vertical-align: top; }
        td.label { width: 40%; font-size: 10px; text-transform: uppercase; letter-spacing: 0.04em; color: #555; }
        td.value { font-weight: 600; font-size: 12px; text-align: right; }

        .qr { text-align: center; margin: 12px 0; }
        .qr .hint { font-size: 10px; color: #555; margin-top: 4px; }

        .footer { margin-top: 14px; border-top: 1px dashed #999; padding-top: 8px; font-size: 9px; color: #555; text-align: center; }
        .signature { margin-top: 18px; border-top: 1px solid #111; width: 200px; text-align: center; font-size: 10px; padding-top: 4px; float: left; }

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
        }

        .print-btn { text-align: center; margin: 16px 0; }
        .print-btn button, .print-btn a {
            display: inline-block; padding: 10px 24px; font-size: 14px; cursor: pointer;
            border-radius: 8px; text-decoration: none; margin: 0 4px;
        }

        @media print {
            @page { size: 5in 7in; margin: 0; }
            body { background: #fff; }
            .ticket { width: 5in; min-height: 7in; margin: 0; padding: 0.35in; box-shadow: none; }
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
        <div style="font-size:11px; font-weight:700; text-align:center; border:2px dashed #111; padding:4px; margin-bottom:10px;">OFFICIAL COPY</div>
    @else
        <div style="font-size:11px; font-weight:700; text-align:center; border:2px dashed #111; padding:4px; margin-bottom:10px;">VIOLATOR COPY</div>
    @endif

    <table>
        <tr><td class="label">Violation</td><td class="value">{{ $citation->violationType->name }}</td></tr>
        <tr><td class="label">Penalty Amount</td><td class="value">₱{{ number_format($citation->penalty_amount, 2) }}</td></tr>
        <tr><td class="label">Plate Number</td><td class="value">{{ $citation->vehicle_plate }}</td></tr>
        <tr><td class="label">Vehicle</td><td class="value">{{ $citation->vehicle_make }} {{ $citation->vehicle_model }} {{ $citation->vehicle_type ? '('.$citation->vehicle_type.')' : '' }}</td></tr>
        <tr><td class="label">Color</td><td class="value">{{ $citation->vehicle_color ?? '—' }}</td></tr>
        <tr><td class="label">Driver</td><td class="value">{{ $citation->driver_name ?? '—' }}</td></tr>
        <tr><td class="label">Date Issued</td><td class="value">{{ $citation->issued_at?->format('F d, Y h:i A') }}</td></tr>
        <tr><td class="label">Due Date</td><td class="value">{{ $citation->due_date?->format('F d, Y') }}</td></tr>
        <tr><td class="label">Location</td><td class="value">{{ $citation->location ?? '—' }}</td></tr>
        <tr><td class="label">Issued By</td><td class="value">{{ $citation->enforcer->name ?? '—' }}</td></tr>
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

    <div class="qr">
        {!! $citation->getQRCodeSvg(180) !!}
        <div class="hint">Scan to view citation details or pay online</div>
    </div>

    <div class="signature">
        {{ $citation->enforcer->name ?? 'Name of Issuing Officer' }}
    </div>

    <div class="footer">
        For questions or to pay at the office, present citation number <b>{{ $citation->citation_number }}</b>.<br>
        Generated by {{ config('itevcms.app_name') }} on {{ now()->format('F d, Y h:i A') }}
    </div>
</div>
</body>
</html>