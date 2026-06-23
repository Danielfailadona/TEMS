@php
    $memberCount = $team->members->count();
    $zoneCount = $team->zones()->count();
    $totalZones = count($zones);
    $teamZoneIds = $team->zones->pluck('id')->toArray();
@endphp

@extends('layouts.app')

@section('title', 'Edit Team')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Edit Team</h1>
        <div class="text-muted small">{{ $team->name }}</div>
    </div>
    <a href="{{ route('teams.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Teams</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-primary bg-opacity-10 p-2">
                    <i class="bi bi-people text-primary"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:0.75rem">Members</div>
                    <div class="fw-bold">{{ $memberCount }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-success bg-opacity-10 p-2">
                    <i class="bi bi-geo-alt text-success"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:0.75rem">Zones</div>
                    <div class="fw-bold" id="assigned-zone-count">{{ $zoneCount }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-info bg-opacity-10 p-2">
                    <i class="bi bi-globe text-info"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:0.75rem">Total Zones</div>
                    <div class="fw-bold">{{ $totalZones }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle {{ $team->is_active ? 'bg-success' : 'bg-secondary' }} bg-opacity-10 p-2">
                    <i class="bi bi-check-circle text-{{ $team->is_active ? 'success' : 'secondary' }}"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:0.75rem">Status</div>
                    <div class="fw-bold">{{ $team->is_active ? 'Active' : 'Inactive' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('teams.update', $team) }}" id="team-form">
    @csrf
    @method('PUT')
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Team Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $team->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $team->description) }}</textarea>
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
                        <div id="lead-radios" class="d-flex flex-wrap gap-2">
                            @foreach ($team->members as $member)
                                <label class="d-flex align-items-center gap-2" style="cursor:pointer">
                                    <input type="radio" name="leader_id" value="{{ $member->id }}" class="form-check-input" @checked(old('leader_id', $team->leader_id) == $member->id)>
                                    <span class="small">{{ $member->name }}</span>
                                </label>
                            @endforeach
                            @if ($team->members->isEmpty())
                                <span class="text-muted small">Select members below first.</span>
                            @endif
                        </div>
                        <input type="hidden" name="leader_id" id="leader-id-input" value="{{ old('leader_id', $team->leader_id) }}">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Members</label>
                        <div class="d-flex flex-column gap-1 mt-1" id="member-list">
                            @foreach ($enforcers as $enforcer)
                                @php
                                    $isMember = $team->members->contains($enforcer->id);
                                    $initial = strtoupper(substr($enforcer->name, 0, 1));
                                    $colors = ['primary', 'success', 'warning', 'danger', 'info', 'secondary', 'dark'];
                                    $colorIndex = crc32($enforcer->email) % count($colors);
                                    $avatarColor = $colors[$colorIndex];
                                @endphp
                                <label class="d-flex align-items-center gap-3 p-2 rounded border member-row {{ $isMember ? 'border-primary bg-primary bg-opacity-10' : '' }}" style="cursor:pointer">
                                    <input type="checkbox" name="members[]" value="{{ $enforcer->id }}" class="form-check-input member-checkbox" {{ $isMember ? 'checked' : '' }}>
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
                        <div class="form-text mt-1">Only Enforcer roles are available.</div>
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
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $team->is_active))>
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
                        <span class="small text-muted">Click a zone marker to assign or unassign.</span>
                        <span class="small fw-semibold" id="assignment-status">
                            <span id="assigned-label">{{ $zoneCount }}</span> / {{ $totalZones }} assigned
                        </span>
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
            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg"></i> Update Team</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
@vite('resources/js/zone-picker.js')
<script>
const TEAM_ZONES = @json($zones);
const ASSIGNED_IDS = @json($teamZoneIds);
document.addEventListener('DOMContentLoaded', function () {
    const memberCheckboxes = document.querySelectorAll('.member-checkbox');
    const leadRadios = document.getElementById('lead-radios');
    const leadInput = document.getElementById('leader-id-input');

    function updateLeadRadios() {
        const checked = document.querySelectorAll('.member-checkbox:checked');
        if (checked.length === 0) {
            leadRadios.innerHTML = '<span class="text-muted small">Select members below first.</span>';
            leadInput.value = '';
            return;
        }
        let html = '';
        const currentLead = leadInput.value;
        checked.forEach(cb => {
            const row = cb.closest('.member-row');
            const name = row.querySelector('.fw-semibold').textContent.trim();
            const id = cb.value;
            const isChecked = id === currentLead;
            html += `<label class="d-flex align-items-center gap-2 me-3" style="cursor:pointer">
                <input type="radio" name="_lead_radio" value="${id}" class="form-check-input lead-radio" ${isChecked ? 'checked' : ''}>
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
        zoneFn('team-zone-map', {
            clickable: true,
            zoom: 10,
            zones: TEAM_ZONES,
            assignedZoneIds: ASSIGNED_IDS,
            assignEndpoint: '{{ route("teams.zones.toggle", $team) }}',
            onAssign: function (data) {
                document.getElementById('assigned-label').textContent = data.assigned_count;
                document.getElementById('assigned-zone-count').textContent = data.assigned_count;
            },
        });
    }
});
</script>
@endpush
