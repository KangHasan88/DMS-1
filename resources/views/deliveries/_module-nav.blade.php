@php
    $isOperational = request()->routeIs('deliveries.*');
    $isRouteSessions = request()->routeIs('delivery-route-sessions.*');
    $isCoverage = request()->routeIs('delivery-coverage.*');
    $isVehicles = request()->routeIs('delivery-vehicles.*');
    $isDrivers = request()->routeIs('delivery-drivers.*');
    $isVendors = request()->routeIs('delivery-vendors.*');
    $isTimeSlots = request()->routeIs('delivery-time-slots.*');
    $isSettings = $isCoverage || $isVehicles || $isDrivers || $isVendors || $isTimeSlots;
@endphp

<nav class="delivery-module-nav" aria-label="Navigasi modul pengiriman">
    <div class="delivery-module-tabs">
        <a href="{{ route('deliveries.index') }}" class="delivery-module-tab {{ $isOperational ? 'active' : '' }}">
            <i class="bi bi-truck"></i>
            <span>Operasional</span>
        </a>
        <a href="{{ route('delivery-coverage.index') }}" class="delivery-module-tab {{ $isSettings ? 'active' : '' }}">
            <i class="bi bi-sliders"></i>
            <span>Pengaturan Pengiriman</span>
        </a>
        <a href="{{ route('delivery-route-sessions.index') }}" class="delivery-module-tab {{ $isRouteSessions ? 'active' : '' }}">
            <i class="bi bi-signpost-split"></i>
            <span>Sesi Rute</span>
        </a>
    </div>

    @if($isSettings)
    <div class="delivery-settings-links" aria-label="Menu pengaturan pengiriman">
        <a href="{{ route('delivery-coverage.index') }}" class="{{ $isCoverage ? 'active' : '' }}">
            <i class="bi bi-pin-map"></i> Coverage
        </a>
        <a href="{{ route('delivery-vehicles.index') }}" class="{{ $isVehicles ? 'active' : '' }}">
            <i class="bi bi-truck-front"></i> Armada
        </a>
        <a href="{{ route('delivery-drivers.index') }}" class="{{ $isDrivers ? 'active' : '' }}">
            <i class="bi bi-person-badge"></i> Driver
        </a>
        <a href="{{ route('delivery-vendors.index') }}" class="{{ $isVendors ? 'active' : '' }}">
            <i class="bi bi-box-arrow-up-right"></i> Ekspedisi
        </a>
        <a href="{{ route('delivery-time-slots.index') }}" class="{{ $isTimeSlots ? 'active' : '' }}">
            <i class="bi bi-clock"></i> Slot Waktu
        </a>
    </div>
    @endif
</nav>

@once
<style>
.delivery-module-nav {
    margin-bottom: 1rem;
    border: 1px solid var(--k-gray-200);
    border-radius: 6px;
    background: #fff;
    overflow: hidden;
}
.delivery-module-tabs {
    display: flex;
    align-items: center;
    min-height: 48px;
    padding: 0 .75rem;
    border-bottom: 1px solid var(--k-gray-200);
    gap: .25rem;
}
.delivery-module-tab {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    min-height: 48px;
    padding: 0 1rem;
    color: var(--k-gray-600);
    font-size: .8rem;
    font-weight: 600;
    text-decoration: none;
}
.delivery-module-tab:hover {
    color: var(--k-blue);
}
.delivery-module-tab.active {
    color: var(--k-blue);
}
.delivery-module-tab.active::after {
    position: absolute;
    right: 1rem;
    bottom: -1px;
    left: 1rem;
    height: 3px;
    background: var(--k-blue);
    content: "";
}
.delivery-settings-links {
    display: flex;
    align-items: center;
    gap: .25rem;
    min-height: 46px;
    padding: .45rem .75rem;
    overflow-x: auto;
}
.delivery-settings-links a {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    min-height: 34px;
    padding: 0 .75rem;
    border: 1px solid transparent;
    border-radius: 5px;
    color: var(--k-gray-600);
    font-size: .75rem;
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
}
.delivery-settings-links a:hover {
    border-color: var(--k-gray-200);
    color: var(--k-blue);
    background: var(--k-gray-50);
}
.delivery-settings-links a.active {
    border-color: #bfd5f5;
    color: var(--k-blue);
    background: #eef5ff;
}
@media (max-width: 640px) {
    .delivery-module-tabs {
        padding: 0 .35rem;
    }
    .delivery-module-tab {
        justify-content: center;
        flex: 1;
        padding: 0 .5rem;
    }
}
</style>
@endonce
