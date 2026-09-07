<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Status — {{ config('itevcms.app_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 560px;">
    <div class="text-center mb-4">
        <img src="{{ asset('images/transpo_enfo_orig.png') }}" alt="TEMs" height="56" class="mb-2">
        <h4 class="mb-0 fw-bold">{{ config('itevcms.app_name') }}</h4>
        <p class="text-muted small mb-0">Transportation Enforcement Management System</p>
    </div>

    @if ($payment->paid_at)
        <div class="card shadow-sm border-success text-center mb-4">
            <div class="card-body py-5">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                <h3 class="mt-3 mb-1">Payment Successful!</h3>
                <p class="text-muted mb-4">Your payment has been processed successfully.</p>

                <div class="row g-3 text-start mx-auto" style="max-width: 380px;">
                    <div class="col-6">
                        <small class="text-muted d-block">Receipt #</small>
                        <strong>{{ $payment->receipt_number }}</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Citation #</small>
                        <strong>{{ $citation->citation_number }}</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Amount Paid</small>
                        <strong>₱{{ number_format($payment->amount, 2) }}</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Payment Method</small>
                        <strong>{{ $payment->online_payment_method ? ucfirst($payment->online_payment_method) : 'Online Payment' }}</strong>
                    </div>
                </div>

                <div class="d-flex justify-content-center gap-2 mt-4 flex-wrap">
                    <a href="{{ route('public.citation.print', ['id' => $citation->id, 'token' => $citation->getValidationToken()]) }}"
                       class="btn btn-outline-primary">
                        <i class="bi bi-printer me-2"></i>Print Receipt
                    </a>
                    <a href="{{ route('public.citation.ticket', ['id' => $citation->id, 'token' => $citation->getValidationToken()]) }}"
                       class="btn btn-primary">
                        <i class="bi bi-file-earmark-text me-2"></i>View Ticket
                    </a>
                </div>
                @if ($citation->evidence->isNotEmpty())
                    <div class="card mx-auto mt-4 text-start border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="text-muted text-uppercase small fw-semibold mb-3">
                                <i class="bi bi-images me-1"></i>Evidence ({{ $citation->evidence->count() }})
                            </h6>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($citation->evidence as $evidence)
                                    <img src="{{ asset('storage/'.$evidence->file_path) }}"
                                         alt="Evidence"
                                         class="rounded border"
                                         style="width: 96px; height: 96px; object-fit: cover; cursor: zoom-in;"
                                         onclick="openModal('{{ asset('storage/'.$evidence->file_path) }}')">
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="card shadow-sm border-warning text-center mb-4">
            <div class="card-body py-5">
                <div class="mx-auto mb-3 spinner-border text-warning" style="width:3rem;height:3rem;" role="status"></div>
                <h3 class="mb-2">Payment Being Processed</h3>
                <p class="text-muted mx-auto" style="max-width: 380px;">
                    Your payment is being verified. This usually takes a few seconds. This page will update automatically once your payment is confirmed.
                </p>
                <a href="{{ route('public.citation.ticket', ['id' => $citation->id, 'token' => $citation->getValidationToken()]) }}"
                   class="btn btn-primary mt-2">
                    <i class="bi bi-file-earmark-text me-2"></i>Back to Ticket
                </a>
            </div>
        </div>
        <script>
            (function () {
                var attempts = 0;
                var tick = function () {
                    if (attempts >= 12) return;
                    attempts++;
                    setTimeout(function () { window.location.reload(); }, 5000);
                };
                tick();
            })();
        </script>
    @endif
</div>

<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Evidence Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img id="modalImage" src="" alt="Evidence" class="img-fluid" style="max-height: 80vh;">
            </div>
        </div>
    </div>
</div>

<script>
    function openModal(src) {
        document.getElementById('modalImage').src = src;
        new bootstrap.Modal(document.getElementById('imageModal')).show();
    }
</script>
</body>
</html>