@extends('layouts.app')

@section('title', 'Create Team')

@push('styles')
<style>
    .stat-card-sm {
        flex: 1;
        min-width: 140px;
        padding: 1rem 1.25rem;
        border-radius: 0.75rem;
        background: var(--itevcms-card);
        border: 1px solid var(--itevcms-border);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .stat-card-sm:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06); }
    .stat-card-sm .stat-value { font-size: 1.5rem; font-weight: 800; line-height: 1.2; }
    .stat-card-sm .stat-label {
        font-size: 0.7rem;
        color: var(--itevcms-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-weight: 600;
    }
    .avatar-stack { display: inline-flex; align-items: center; }
    .avatar-stack > .avatar-stack-item { margin-left: -8px; border: 2px solid var(--itevcms-card); }
    .avatar-stack > .avatar-stack-item:first-child { margin-left: 0; }
    .member-row { transition: background-color 0.15s ease, border-color 0.15s ease; }
    .member-row.is-selected { border-color: var(--itevcms-accent); background: rgba(37, 99, 235, 0.06); }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 animate-on-load">
    <p class="text-muted mb-0 small">Group enforcers by patrol unit and assign coverage leaders.</p>
    <a href="{{ route('teams.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Teams</a>
</div>

<div class="d-flex gap-3 mb-4 flex-wrap animate-on-load">
    <div class="stat-card-sm">
        <div class="stat-value" id="stat-member-count">0</div>
        <div class="stat-label">Members</div>
    </div>
    <div class="stat-card-sm">
        <div class="stat-value" style="color:#2563eb;">{{ count($zones) }}</div>
        <div class="stat-label">Total Zones</div>
    </div>
    <div class="stat-card-sm">
        <div class="stat-value" style="color:#059669;">0</div>
        <div class="stat-label">Assigned Zones</div>
    </div>
</div>

<form method="POST" action="{{ route('teams.store') }}" id="team-form">
    @csrf
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card stat-card mb-4 animate-on-load">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Team Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Alpha Response Unit" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="What does this team do?"></textarea>
                    </div>
                </div>
            </div>

            <div class="card stat-card mb-4 animate-on-load">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="mb-0"><i class="bi bi-people me-2"></i>Team Members</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Lead</label>
                        <div id="lead-radios" class="text-muted small pt-1">Pick members below, then choose a leader here.</div>
                    </div>
                    <div class="mb-0">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <label class="form-label mb-0">Members</label>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-stack" id="member-preview"></div>
                                <span class="text-muted small" id="member-count-label">0 selected</span>
                            </div>
                        </div>
                        <input type="text" id="member-search" class="form-control form-control-sm mb-2" placeholder="Search enforcers by name or email...">
                        <div class="d-flex flex-column gap-1 mt-1" id="member-list">
                            @foreach ($enforcers as $enforcer)
                                @php
                                    $initial = strtoupper(substr($enforcer->name, 0, 1));
                                    $colors = ['primary', 'success', 'warning', 'danger', 'info', 'secondary', 'dark'];
                                    $colorIndex = crc32($enforcer->email) % count($colors);
                                    $avatarColor = $colors[$colorIndex];
                                @endphp
                                <label class="d-flex align-items-center gap-3 p-2 rounded border member-row" style="cursor:pointer">
                                    <input type="checkbox" name="members[]" value="{{ $enforcer->id }}" class="form-check-input member-checkbox">
                                    <span class="avatar-initial rounded-circle bg-{{ $avatarColor }} bg-opacity-10 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px">
                                        <span class="fw-bold text-{{ $avatarColor }} small">{{ $initial }}</span>
                                    </span>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold small">{{ $enforcer->name }}</div>
                                        <div class="text-muted" style="font-size:0.8rem">{{ $enforcer->email }}</div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        <div class="d-flex align-items-center justify-content-between mt-2" id="member-pagination" hidden>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="member-prev"><i class="bi bi-chevron-left"></i> Prev</button>
                            <span class="small text-muted" id="member-page-info"></span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="member-next">Next <i class="bi bi-chevron-right"></i></button>
                        </div>
                        <div class="form-text mt-1">Check members to include them. Only Enforcer roles are available.</div>
                    </div>
                </div>
            </div>

            <div class="card stat-card mb-4 animate-on-load">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Settings</h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" checked>
                        <label class="form-check-label" for="is_active">Active team</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card stat-card animate-on-load">
                <div class="card-header bg-transparent border-bottom">
                    <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Zone Coverage</h5>
                </div>
                <div class="card-body p-0">
                    <div id="team-zone-map" class="zone-map"></div>
                </div>
                <div class="card-footer bg-transparent border-top">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted">Assigned zones will appear on the map after creation.</span>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('zones.index') }}" class="btn btn-sm btn-outline-primary flex-fill"><i class="bi bi-eye me-1"></i>View Zones</a>
                        <a href="{{ route('zones.create') }}" class="btn btn-sm btn-outline-success flex-fill"><i class="bi bi-plus me-1"></i>Manage Zones</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="sticky-save">
        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('teams.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Save Team</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
@vite('resources/js/zone-picker.js')
<script>
const TEAM_ZONES = @json($zones);
document.addEventListener('DOMContentLoaded', function () {
    const memberCheckboxes = document.querySelectorAll('.member-checkbox');
    const leadRadios = document.getElementById('lead-radios');
    const leadInput = document.createElement('input');
    leadInput.type = 'hidden';
    leadInput.name = 'leader_id';
    leadInput.id = 'leader-id-input';
    leadRadios.parentNode.appendChild(leadInput);

    const memberPreview = document.getElementById('member-preview');
    const memberCountLabel = document.getElementById('member-count-label');
    const memberSearch = document.getElementById('member-search');
    const memberRows = Array.from(document.querySelectorAll('#member-list .member-row'));
    const palette = ['primary', 'success', 'warning', 'danger', 'info', 'secondary', 'dark'];

    function crc32(str) {
        let crc = 0xFFFFFFFF;
        for (let i = 0; i < str.length; i++) {
            const ch = str.charCodeAt(i);
            crc = crc ^ ch;
            for (let j = 7; j >= 0; j--) {
                crc = (crc >>> 1) ^ (0xEDB88320 & -(crc & 1));
            }
        }
        return (crc ^ 0xFFFFFFFF) >>> 0;
    }

    function updateMemberPreview() {
        const selected = Array.from(document.querySelectorAll('.member-checkbox:checked'));
        memberPreview.innerHTML = '';
        selected.slice(0, 4).forEach(cb => {
            const row = cb.closest('.member-row');
            const name = row.querySelector('.fw-semibold').textContent.trim();
            const initial = name.charAt(0).toUpperCase();
            const email = row.querySelector('.text-muted').textContent.trim();
            const color = palette[Math.abs(crc32(email)) % palette.length];
            const span = document.createElement('span');
            span.className = 'avatar-initial avatar-stack-item rounded-circle d-inline-flex align-items-center justify-content-center';
            span.style.width = '24px'; span.style.height = '24px'; span.style.fontSize = '0.7rem';
            span.style.background = 'var(--bs-' + color + '-bg-subtle)';
            span.style.color = 'var(--bs-' + color + ')';
            span.title = name;
            span.textContent = initial;
            memberPreview.appendChild(span);
        });
        if (selected.length > 4) {
            const more = document.createElement('span');
            more.className = 'avatar-initial avatar-stack-item rounded-circle bg-light d-inline-flex align-items-center justify-content-center';
            more.style.width = '24px'; more.style.height = '24px'; more.style.fontSize = '0.7rem';
            more.textContent = '+' + (selected.length - 4);
            memberPreview.appendChild(more);
        }
        memberCountLabel.textContent = selected.length + ' selected';
        document.getElementById('stat-member-count').textContent = selected.length;
    }

    function updateLeadRadios() {
        const checked = document.querySelectorAll('.member-checkbox:checked');
        if (checked.length === 0) {
            leadRadios.innerHTML = '<span class="text-muted small">Pick members below, then choose a leader here.</span>';
            leadInput.value = '';
            return;
        }
        let html = '';
        checked.forEach(cb => {
            const row = cb.closest('.member-row');
            const name = row.querySelector('.fw-semibold').textContent.trim();
            const id = cb.value;
            html += `<label class="d-flex align-items-center gap-2 me-3" style="cursor:pointer">
                <input type="radio" name="_lead_radio" value="${id}" class="form-check-input lead-radio">
                <span class="small">${name}</span>
            </label>`;
        });
        leadRadios.innerHTML = html;
        leadRadios.querySelectorAll('.lead-radio').forEach(r => {
            r.addEventListener('change', function () {
                leadInput.value = this.value;
            });
        });
    }

    memberCheckboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            const row = this.closest('.member-row');
            if (this.checked) {
                row.classList.add('border-primary', 'is-selected');
            } else {
                row.classList.remove('border-primary', 'is-selected');
                const checkedLead = leadRadios.querySelector('.lead-radio:checked');
                if (checkedLead && checkedLead.value === this.value) {
                    leadInput.value = '';
                }
            }
            updateLeadRadios();
            updateMemberPreview();
        });
    });

    const pagination = document.getElementById('member-pagination');
    const PER_PAGE = 5;
    let currentPage = 1;
    let filteredRows = memberRows;

    // Search filter
    memberSearch.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        filteredRows = memberRows.filter(row => q === '' || row.textContent.toLowerCase().includes(q));
        currentPage = 1;
        updatePagination();
    });

    function updatePagination() {
        const totalPages = Math.max(1, Math.ceil(filteredRows.length / PER_PAGE));
        if (currentPage > totalPages) currentPage = totalPages;
        memberRows.forEach(row => row.classList.add('d-none'));
        const start = (currentPage - 1) * PER_PAGE;
        filteredRows.slice(start, start + PER_PAGE).forEach(row => row.classList.remove('d-none'));
        document.getElementById('member-page-info').textContent = 'Page ' + currentPage + ' of ' + totalPages;
        document.getElementById('member-prev').disabled = currentPage <= 1;
        document.getElementById('member-next').disabled = currentPage >= totalPages;
        pagination.hidden = filteredRows.length <= PER_PAGE;
    }

    document.getElementById('member-prev').addEventListener('click', () => { if (currentPage > 1) { currentPage--; updatePagination(); } });
    document.getElementById('member-next').addEventListener('click', () => { if (currentPage < totalPages) { currentPage++; updatePagination(); } });

    updatePagination();

    const zoneFn = window.__zonePicker?.initTeamZonePicker;
    if (typeof zoneFn === 'function') {
        zoneFn('team-zone-map', { clickable: false, zoom: 10, zones: TEAM_ZONES });
    }
});
</script>
@endpush
