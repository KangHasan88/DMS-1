<div class="stats-grid" style="margin-bottom: 1.5rem;">
    @foreach($items as $item)
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background: {{ $item['bg'] ?? 'var(--k-green-light)' }};">
                    <i class="bi {{ $item['icon'] ?? 'bi-bar-chart' }}" style="color: {{ $item['color'] ?? 'var(--k-green)' }};"></i>
                </div>
            </div>
            <div class="stat-value">{{ $item['value'] }}</div>
            <div class="stat-label">{{ $item['label'] }}</div>
        </div>
    @endforeach
</div>
