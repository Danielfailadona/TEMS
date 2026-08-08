@extends('layouts.app')

@section('title', 'Create Team')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Create Team</h1>
    </div>
    <a href="{{ route('teams.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Teams</a>
</div>

<form method="POST" action="{{ route('teams.store') }}" id="team-form">
    @csrf
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
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

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="bi bi-people me-2"></i>Team Members</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Lead</label>
                        <div id="lead-radios" class="text-muted small pt-1">Select members below first.</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Members</label>
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

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
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
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Zone Coverage</h5>
                </div>
                <div class="card-body p-0">
                    <div id="team-zone-map" class="zone-map"></div>
                </div>
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted">Assigned zones will appear on the map after creation.</span>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('zones.index') }}" class="btn btn-sm btn-outline-primary flex-fill"><i class="bi bi-eye"></i> View Zones</a>
                        <a href="{{ route('zones.create') }}" class="btn btn-sm btn-outline-success flex-fill"><i class="bi bi-plus"></i> Manage Zones</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="sticky-save">
        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('teams.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg"></i> Save Team</button>
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

    function updateLeadRadios() {
        const checked = document.querySelectorAll('.member-checkbox:checked');
        if (checked.length === 0) {
            leadRadios.innerHTML = '<span class="text-muted small">Select members below first.</span>';
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
                row.classList.add('border-primary', 'bg-primary', 'bg-opacity-10');
            } else {
                row.classList.remove('border-primary', 'bg-primary', 'bg-opacity-10');
                const checkedLead = leadRadios.querySelector('.lead-radio:checked');
                if (checkedLead && checkedLead.value === this.value) {
                    leadInput.value = '';
                }
            }
            updateLeadRadios();
        });
    });

    const zoneFn = window.__zonePicker?.initTeamZonePicker;
    if (typeof zoneFn === 'function') {
        zoneFn('team-zone-map', { clickable: false, zoom: 10, zones: TEAM_ZONES });
    }

    const memberRows = Array.from(document.querySelectorAll('#member-list .member-row'));
    const pagination = document.getElementById('member-pagination');
    if (memberRows.length > 5) {
        const PER_PAGE = 5;
        const prevBtn = document.getElementById('member-prev');
        const nextBtn = document.getElementById('member-next');
        const info = document.getElementById('member-page-info');
        const totalPages = Math.ceil(memberRows.length / PER_PAGE);
        let currentPage = 1;

        function renderMemberPage() {
            memberRows.forEach((row, i) => {
                row.classList.toggle('d-none', i < (currentPage - 1) * PER_PAGE || i >= currentPage * PER_PAGE);
            });
            info.textContent = 'Page ' + currentPage + ' of ' + totalPages;
            prevBtn.disabled = currentPage <= 1;
            nextBtn.disabled = currentPage >= totalPages;
        }

        prevBtn.addEventListener('click', () => { if (currentPage > 1) { currentPage--; renderMemberPage(); } });
        nextBtn.addEventListener('click', () => { if (currentPage < totalPages) { currentPage++; renderMemberPage(); } });

        pagination.hidden = false;
        renderMemberPage();
    }
});
</script>
@endpush
