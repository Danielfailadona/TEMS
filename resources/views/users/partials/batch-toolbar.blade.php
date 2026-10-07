{{-- Batch actions toolbar. Included by the users index and by the AJAX users card so it
     stays consistent and keeps working after a filter/pagination refresh. --}}
<div class="mb-3 d-none" id="batch-actions-toolbar" style="position: sticky; top: 0; z-index: 1020; background: rgba(255,255,255,0.98); padding: 0.6rem 1rem; border: 1px solid #e2e8f0; border-radius: 1rem; box-shadow: 0 4px 14px rgba(0,0,0,0.07);">
    <div class="d-flex flex-wrap align-items-center gap-2">
        <span class="badge bg-primary rounded-pill px-3 py-2" id="batch-selected-count">0 selected</span>

        {{-- Pending actions (Approve / Reject) --}}
        <div class="d-flex align-items-center gap-1 bg-light border rounded-3 p-1" id="batch-pending-group">
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
        <div class="d-flex align-items-center gap-1 bg-light border rounded-3 p-1" id="batch-status-group">
            <form method="POST" action="{{ route('users.batch-action') }}" class="d-inline" id="batch-suspend-form">
                @csrf
                <input type="hidden" name="action" value="suspend">
                <input type="hidden" name="user_ids" id="batch-suspend-ids">
                <button type="submit" class="btn btn-warning btn-sm rounded-2" disabled id="batch-suspend-btn" data-bs-toggle="tooltip" title="Suspend selected approved or pending users">
                    <i class="bi bi-lock me-1"></i> Suspend
                </button>
            </form>

            <form method="POST" action="{{ route('users.batch-action') }}" class="d-inline" id="batch-unsuspend-form">
                @csrf
                <input type="hidden" name="action" value="unsuspend">
                <input type="hidden" name="user_ids" id="batch-unsuspend-ids">
                <button type="submit" class="btn btn-success btn-sm rounded-2" disabled id="batch-unsuspend-btn" data-bs-toggle="tooltip" title="Unsuspend selected suspended users">
                    <i class="bi bi-unlock me-1"></i> Unsuspend
                </button>
            </form>
        </div>

        <button type="button" class="btn btn-sm btn-link text-muted ms-auto px-2 d-inline-flex align-items-center justify-content-center" id="batch-deselect-btn">
            <i class="bi bi-x-circle me-1"></i>Deselect
        </button>
    </div>
</div>