{{-- Batch actions toolbar. Included by the users index and by the AJAX users card so it
     stays consistent and keeps working after a filter/pagination refresh. --}}
<div class="mb-2 d-none" id="batch-actions-toolbar" style="background: rgba(248,250,252,0.9); padding: 0.5rem 0.75rem; border-radius: 0.75rem;">
    <div class="d-flex flex-wrap align-items-center gap-2">
        <span class="badge bg-primary rounded-pill px-3 py-2" id="batch-selected-count">0 selected</span>
        <span class="vr mx-2"></span>

        {{-- Pending actions (Approve / Reject) --}}
        <div class="d-flex align-items-center gap-1" id="batch-pending-group" style="display: none;">
            <form method="POST" action="{{ route('users.batch-action') }}" class="d-inline-flex align-items-center" id="batch-approve-form">
                @csrf
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="user_ids" id="batch-approve-ids">
                <button type="submit" class="btn btn-success btn-sm rounded-2" disabled id="batch-approve-btn" data-bs-toggle="tooltip" title="Approve selected pending users">
                    <i class="bi bi-check-lg me-1"></i> Approve
                </button>
            </form>

            {{-- Reject opens the shared reason modal; the modal owns the user_ids input --}}
            <form method="POST" action="{{ route('users.batch-action') }}" class="d-inline-flex align-items-center" id="batch-reject-form">
                @csrf
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="user_ids" id="batch-reject-ids">
                <button type="submit" class="btn btn-danger btn-sm rounded-2" disabled id="batch-reject-btn" title="Reject selected pending users">
                    <i class="bi bi-x-lg me-1"></i> Reject
                </button>
            </form>
        </div>

        {{-- Status actions (Suspend / Unsuspend) --}}
        <div class="d-flex align-items-center gap-1" id="batch-status-group" style="display: none;">
            <form method="POST" action="{{ route('users.batch-action') }}" class="d-inline-flex align-items-center" id="batch-suspend-form">
                @csrf
                <input type="hidden" name="action" value="suspend">
                <input type="hidden" name="user_ids" id="batch-suspend-ids">
                <button type="submit" class="btn btn-warning btn-sm rounded-2" disabled id="batch-suspend-btn" data-bs-toggle="tooltip" title="Suspend selected approved or pending users">
                    <i class="bi bi-lock me-1"></i> Suspend
                </button>
            </form>

            <form method="POST" action="{{ route('users.batch-action') }}" class="d-inline-flex align-items-center" id="batch-unsuspend-form">
                @csrf
                <input type="hidden" name="action" value="unsuspend">
                <input type="hidden" name="user_ids" id="batch-unsuspend-ids">
                <button type="submit" class="btn btn-success btn-sm rounded-2" disabled id="batch-unsuspend-btn" data-bs-toggle="tooltip" title="Unsuspend selected suspended users">
                    <i class="bi bi-unlock me-1"></i> Unsuspend
                </button>
            </form>
        </div>

        <button type="button" class="btn btn-sm btn-link text-muted px-2 d-inline-flex align-items-center justify-content-center" id="batch-deselect-btn">
            <i class="bi bi-x-circle me-1"></i>Deselect
        </button>
    </div>
</div>