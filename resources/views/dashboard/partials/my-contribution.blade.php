{{-- Reusable "My Contribution" KPI tiles. Expects $contribution = ['scope' => ..., 'cards' => [...]]. --}}
@if (!empty($contribution['cards']))
    <div class="section-label">{{ $contribution['scope'] ?? 'My Contribution' }}</div>
    <div class="row g-3 mb-4 animate-on-load" id="my-contribution">
        @foreach ($contribution['cards'] as $card)
            <div class="col-sm-6 col-xl-3">
                <div class="card stat-card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-{{ $card['color'] }}-subtle text-{{ $card['color'] }}" style="width:2.75rem;height:2.75rem;">
                            <i class="bi {{ $card['icon'] }} fs-5"></i>
                        </span>
                        <div class="min-w-0">
                            <div class="text-muted small">{{ $card['label'] }}</div>
                            <div class="h4 mb-0">{{ $card['value'] }}</div>
                            <div class="text-muted" style="font-size:0.7rem;">{{ $card['caption'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
