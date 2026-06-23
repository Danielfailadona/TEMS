<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Release Order — {{ $clamping->release->release_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @page { margin: 1.5cm; }
        body { font-family: 'Courier New', monospace; font-size: 12px; color: #000; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 1rem; margin-bottom: 1.5rem; }
        .header h1 { font-size: 1.1rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0; }
        .header h2 { font-size: 0.95rem; font-weight: 600; margin-top: 0.25rem; }
        .header p { font-size: 0.75rem; margin-bottom: 0; }
        .ref-row { display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 1.5rem; }
        .section-title { font-weight: 700; font-size: 0.75rem; text-transform: uppercase; border-bottom: 1px solid #999; margin-bottom: 0.5rem; padding-bottom: 0.2rem; }
        .info-table { width: 100%; font-size: 0.8rem; margin-bottom: 1rem; }
        .info-table td { padding: 0.2rem 0.5rem; vertical-align: top; }
        .info-table td:first-child { width: 35%; font-weight: 600; }
        .signatures { margin-top: 2rem; display: flex; justify-content: space-between; }
        .signature-block { text-align: center; width: 40%; }
        .signature-line { border-top: 1px solid #000; margin-top: 3rem; padding-top: 0.3rem; font-size: 0.75rem; }
        .footer { text-align: center; font-size: 0.65rem; color: #666; margin-top: 2rem; border-top: 1px solid #ccc; padding-top: 0.5rem; }
        .badge-print { display: inline-block; padding: 0.15rem 0.5rem; font-size: 0.7rem; font-weight: 700; border: 1px solid #000; }
        .no-print { display: none; }
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="text-center mb-2 no-print">
        <button onclick="window.print()" class="btn btn-primary btn-sm">Print</button>
        <button onclick="window.close()" class="btn btn-outline-secondary btn-sm">Close</button>
    </div>

    <div class="header">
        <h1>Vehicle Release Order</h1>
        <h2>Traffic Management Office</h2>
        <p>{{ config('app.name') }} • Official Release Document</p>
    </div>

    <div class="ref-row">
        <span><strong>Release No:</strong> {{ $clamping->release->release_number }}</span>
        <span><strong>Date:</strong> {{ $clamping->release->released_at->format('F d, Y') }}</span>
    </div>

    <div class="section-title">Vehicle Information</div>
    <table class="info-table">
        <tr><td>Plate Number:</td><td>{{ $clamping->vehicle_plate }}</td></tr>
        @if ($clamping->citation?->vehicle_description)
            <tr><td>Vehicle Description:</td><td>{{ $clamping->citation->vehicle_description }}</td></tr>
        @endif
        @if ($clamping->citation?->driver_name)
            <tr><td>Registered Driver:</td><td>{{ $clamping->citation->driver_name }}</td></tr>
        @endif
    </table>

    <div class="section-title">Clamping Details</div>
    <table class="info-table">
        <tr><td>Notice Number:</td><td>{{ $clamping->notice_number }}</td></tr>
        <tr><td>Clamped At:</td><td>{{ $clamping->clamped_at->format('F d, Y h:i A') }}</td></tr>
        <tr><td>Clamping Officer:</td><td>{{ $clamping->officer->name }}</td></tr>
        @if ($clamping->location)
            <tr><td>Location:</td><td>{{ $clamping->location }}</td></tr>
        @endif
    </table>

    @if ($clamping->citation)
        <div class="section-title">Citation & Payment</div>
        <table class="info-table">
            <tr><td>Citation Number:</td><td>{{ $clamping->citation->citation_number }}</td></tr>
            <tr><td>Violation:</td><td>{{ $clamping->citation->violationType->name }}</td></tr>
            <tr><td>Penalty Amount:</td><td>₱{{ number_format($clamping->citation->penalty_amount, 2) }}</td></tr>
            @if ($clamping->citation->payment)
                <tr><td>Payment Method:</td><td>{{ $clamping->citation->payment->payment_method->label() }}</td></tr>
                <tr><td>Receipt Number:</td><td>{{ $clamping->citation->payment->receipt_number }}</td></tr>
                <tr><td>Paid At:</td><td>{{ $clamping->citation->payment->paid_at->format('F d, Y h:i A') }}</td></tr>
            @endif
        </table>
    @endif

    @if ($clamping->release->notes)
        <div class="section-title">Release Notes</div>
        <p style="font-size:0.8rem;">{{ $clamping->release->notes }}</p>
    @endif

    <div style="margin-top:0.5rem; padding:0.5rem; border:1px solid #000; text-align:center; font-size:0.75rem; font-weight:700;">
        THIS VEHICLE IS HEREBY AUTHORIZED FOR RELEASE
    </div>

    <div class="signatures">
        <div class="signature-block">
            <div class="signature-line">Released By</div>
            <div style="font-size:0.75rem; margin-top:0.2rem;">{{ $clamping->release->releasedBy->name }}</div>
        </div>
        <div class="signature-block">
            <div class="signature-line">Received By</div>
            <div style="font-size:0.75rem; margin-top:0.2rem;">________________________</div>
        </div>
    </div>

    <div class="footer">
        This is a computer-generated document. No signature required for digital release.<br>
        {{ config('app.url') }} • Generated {{ now()->format('F d, Y h:i A') }}
    </div>

    <script>
        if (window.location.search.includes('print=true')) {
            window.onload = function() { window.print(); };
        }
    </script>
</body>
</html>
