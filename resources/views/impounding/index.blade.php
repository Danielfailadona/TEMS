@extends('layouts.app')

@section('title', 'Impounding')

@section('content')
<div class="mb-4 text-end">
    <div class="d-flex gap-2 flex-wrap justify-content-end">
        <a href="{{ route('impounding.index') }}" class="btn btn-outline-secondary btn-sm @if(!request('status')) active @endif">Active</a>
        <a href="{{ route('impounding.index', ['status' => 'awaiting_payment']) }}" class="btn btn-outline-danger btn-sm @if(request('status') === 'awaiting_payment') active @endif">Awaiting Payment</a>
        <a href="{{ route('impounding.index', ['status' => 'paid']) }}" class="btn btn-outline-primary btn-sm @if(request('status') === 'paid') active @endif">Paid</a>
        <a href="{{ route('impounding.index', ['status' => 'waiting_release']) }}" class="btn btn-outline-warning btn-sm @if(request('status') === 'waiting_release') active @endif">Waiting Release</a>
        <a href="{{ route('impounding.index', ['status' => 'released']) }}" class="btn btn-outline-success btn-sm @if(request('status') === 'released') active @endif">Released</a>
    </div>
</div>

<div class="card stat-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Plate #</th>
                    <th>Notice #</th>
                    <th>Violation</th>
                    <th>Officer</th>
                    <th>Clamped At</th>
                    <th>Status</th>
                    <th>Evidence</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td class="fw-semibold">{{ $record->vehicle_plate }}</td>
                        <td>{{ $record->notice_number }}</td>
                        <td class="small">{{ $record->citation?->violationType?->name ?? '—' }}</td>
                        <td>{{ $record->officer->name }}</td>
                        <td>{{ $record->clamped_at->format('M d, Y') }}</td>
                        <td><span class="badge {{ $record->status->badgeClass() }}">{{ $record->status->label() }}</span></td>
                        <td>
                            @if ($record->evidence_path)
                                <a href="{{ asset('storage/'.$record->evidence_path) }}" target="_blank">
                                    <img src="{{ asset('storage/'.$record->evidence_path) }}" alt="ev" style="width:36px;height:36px;object-fit:cover;border-radius:4px;">
                                </a>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('impounding.show', $record) }}" class="btn btn-sm btn-outline-primary">View</a>
                            @can('markPaid', $record)
                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#payModal-{{ $record->id }}">Record Payment</button>
                            @endcan
                            @can('markWaitingRelease', $record)
                                <form action="{{ route('impounding.mark-waiting-release', $record) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-warning">Queue Release</button>
                                </form>
                            @endcan
                            @can('processRelease', $record)
                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#releaseModal-{{ $record->id }}">Process Release</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No impounded vehicles found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($records->hasPages())<div class="card-footer bg-white">{{ $records->links() }}</div>@endif
</div>

@foreach ($records as $record)
    @can('markPaid', $record)
        <div class="modal fade" id="payModal-{{ $record->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('impounding.mark-paid', $record) }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Record Payment — {{ $record->vehicle_plate }}</h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-3">Amount due: <strong>₱{{ number_format($record->citation?->penalty_amount ?? 0, 2) }}</strong></p>
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-select" required>
                                    @foreach (App\Enums\PaymentMethod::cases() as $method)
                                        <option value="{{ $method->value }}">{{ $method->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reference Number</label>
                                <input type="text" name="reference_number" class="form-control" placeholder="Optional">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Confirm Payment</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    @can('processRelease', $record)
        <div class="modal fade" id="releaseModal-{{ $record->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('impounding.process-release', $record) }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Process Release — {{ $record->vehicle_plate }}</h5>
                            <button class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Confirm release of vehicle <strong>{{ $record->vehicle_plate }}</strong>?</p>
                            <div class="mb-3">
                                <label class="form-label">Release Notes</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">Release Vehicle</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endforeach
@endsection
