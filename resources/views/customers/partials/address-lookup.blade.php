@php
    $addressName = $addressName ?? 'address';
    $latitudeName = $latitudeName ?? 'latitude';
    $longitudeName = $longitudeName ?? 'longitude';
    $addressLabel = $addressLabel ?? 'Alamat Utama';
    $addressPlaceholder = $addressPlaceholder ?? 'Jl. Contoh No. 123, RT/RW, Kelurahan, Kecamatan, Kota';
    $addressRows = $addressRows ?? 2;
    $addressValue = old($addressName, $addressValue ?? '');
    $latitudeValue = old($latitudeName, $latitudeValue ?? '');
    $longitudeValue = old($longitudeName, $longitudeValue ?? '');
    $addressRequired = $addressRequired ?? false;
    $addressHelp = $addressHelp ?? 'Alamat ini menjadi default invoice & pengiriman.';
    $mapsApiKey = config('services.google_maps.key');
@endphp

@once
<style>
    .customer-form-card {
        padding: 1.05rem 1.15rem;
    }

    .customer-form-card .dms-form-header {
        margin-bottom: 1rem;
        padding-bottom: 0.85rem;
        border-bottom: 1px solid #e4edf7;
    }

    .customer-master-form {
        display: grid;
        gap: 1.05rem;
    }

    .customer-form-section {
        display: grid;
        gap: 0.72rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #e4edf7;
    }

    .customer-form-section:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .customer-section-title {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        color: var(--k-blue-darker);
        font-size: 0.86rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .customer-section-title i {
        color: var(--k-blue);
        font-size: 0.95rem;
    }

    .customer-form-grid {
        column-gap: 1rem;
        row-gap: 0.78rem;
    }

    .customer-form-grid > .form-group {
        margin-bottom: 0;
    }

    .customer-form-grid .form-control {
        min-height: 42px;
        padding-top: 0.56rem;
        padding-bottom: 0.56rem;
    }

    .customer-form-grid .dms-form-help {
        margin-top: 0.22rem;
        line-height: 1.35;
    }

    .customer-credit-section {
        background: linear-gradient(180deg, #fbfdff 0%, #ffffff 100%);
        border: 1px solid #e4edf7;
        border-radius: 8px;
        padding: 0.85rem;
    }

    .customer-credit-section + .customer-form-section {
        margin-top: -0.05rem;
    }

    .customer-stat-strip {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.7rem;
    }

    .customer-stat-strip > div {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        min-height: 42px;
        padding: 0.55rem 0.75rem;
        border: 1px solid #e4edf7;
        border-radius: 8px;
        background: #fbfdff;
    }

    .customer-stat-strip span {
        color: var(--k-gray-500);
        font-size: var(--k-font-xs);
        font-weight: 600;
    }

    .customer-stat-strip strong {
        color: var(--k-blue-darker);
        font-size: var(--k-font-sm);
        font-weight: 700;
    }

    .dms-address-lookup {
        display: grid;
        gap: 0.45rem;
        margin-bottom: 0.45rem;
        padding: 0.58rem;
        border: 1px solid #dbe6f3;
        border-radius: 8px;
        background: #fbfdff;
    }

    .dms-address-search-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 0.55rem;
        align-items: center;
    }

    .dms-address-search-field {
        position: relative;
    }

    .dms-address-search-field i {
        position: absolute;
        left: 0.85rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--k-gray-400);
        pointer-events: none;
    }

    .dms-address-search-field .form-control {
        padding-left: 2.25rem;
    }

    .dms-address-map-link {
        min-height: 40px;
        white-space: nowrap;
    }

    .dms-address-import {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 0.55rem;
        align-items: center;
    }

    .dms-address-import .form-control {
        min-height: 38px;
        font-size: var(--k-font-sm);
    }

    .dms-address-import-status {
        min-height: 1rem;
        color: var(--k-gray-500);
        font-size: var(--k-font-xs);
        line-height: 1.35;
    }

    .dms-address-import-status.is-error {
        color: var(--k-red);
    }

    .dms-address-import-status.is-success {
        color: var(--k-success);
    }

    @media (max-width: 768px) {
        .dms-address-search-row,
        .dms-address-import {
            grid-template-columns: 1fr;
        }

        .customer-stat-strip {
            grid-template-columns: 1fr;
        }
    }
</style>
@endonce

<div class="form-group dms-form-span-2 js-address-block">
    <label class="form-label">
        {{ $addressLabel }}
        @if($addressRequired)
            <span class="dms-required">*</span>
        @endif
    </label>

    <div class="dms-address-lookup">
        <div class="dms-address-search-row">
            <div class="dms-address-search-field">
                <i class="bi bi-geo-alt"></i>
                <input type="text"
                    class="form-control js-address-search"
                    placeholder="Cari alamat di Google Maps..."
                    autocomplete="off">
            </div>
            <a href="https://www.google.com/maps/search/?api=1&query="
                class="dms-btn dms-btn-outline dms-address-map-link js-open-google-maps"
                target="_blank"
                rel="noopener">
                <i class="bi bi-map"></i> Buka Maps
            </a>
        </div>
        <small class="dms-form-help">
            Cari lokasi untuk mengisi alamat dan koordinat otomatis. Jika autocomplete belum aktif, buka Maps lalu tempel link-nya di bawah.
        </small>
        <div class="dms-address-import">
            <input type="url"
                class="form-control js-google-maps-url"
                placeholder="Tempel link Google Maps untuk ambil alamat & koordinat">
            <button type="button" class="dms-btn dms-btn-outline js-import-google-maps">
                <i class="bi bi-clipboard-check"></i> Ambil Data
            </button>
        </div>
        <div class="dms-address-import-status js-address-import-status"></div>
    </div>

    <textarea name="{{ $addressName }}"
        class="form-control"
        rows="{{ $addressRows }}"
        placeholder="{{ $addressPlaceholder }}"
        data-address-target
        {{ $addressRequired ? 'required' : '' }}>{{ $addressValue }}</textarea>
    <small class="dms-form-help">{!! $addressHelp !!}</small>
    @error($addressName) <span class="dms-error">{{ $message }}</span> @enderror
</div>

<div class="form-group">
    <label class="form-label">Latitude</label>
    <input type="text"
        name="{{ $latitudeName }}"
        value="{{ $latitudeValue }}"
        class="form-control"
        placeholder="-6.200000"
        data-latitude-target>
    <small class="dms-form-help">Koordinat lokasi pelanggan (opsional)</small>
    @error($latitudeName) <span class="dms-error">{{ $message }}</span> @enderror
</div>

<div class="form-group">
    <label class="form-label">Longitude</label>
    <input type="text"
        name="{{ $longitudeName }}"
        value="{{ $longitudeValue }}"
        class="form-control"
        placeholder="106.816666"
        data-longitude-target>
    @error($longitudeName) <span class="dms-error">{{ $message }}</span> @enderror
</div>

@once
@push('scripts')
<script>
(function () {
    const reverseGeocodeUrl = @json(route('customers.maps.reverse-geocode'));

    function getScope(input) {
        return input.closest('form') || document;
    }

    function updateMapsLink(input) {
        const scope = getScope(input);
        const addressTarget = scope.querySelector('[data-address-target]');
        const link = input.closest('.js-address-block')?.querySelector('.js-open-google-maps');
        const query = input.value || addressTarget?.value || '';

        if (link) {
            link.href = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(query);
        }
    }

    function applyPlace(input, place) {
        const scope = getScope(input);
        const addressTarget = scope.querySelector('[data-address-target]');
        const latitudeTarget = scope.querySelector('[data-latitude-target]');
        const longitudeTarget = scope.querySelector('[data-longitude-target]');

        if (addressTarget) {
            addressTarget.value = place.formatted_address || place.name || input.value;
        }

        if (place.geometry && place.geometry.location) {
            if (latitudeTarget) {
                latitudeTarget.value = place.geometry.location.lat();
            }
            if (longitudeTarget) {
                longitudeTarget.value = place.geometry.location.lng();
            }
        }

        updateMapsLink(input);
    }

    function getAddressSearch(scope) {
        return scope.querySelector('.js-address-search');
    }

    function getImportStatus(scope) {
        return scope.querySelector('.js-address-import-status');
    }

    function setImportStatus(scope, message, type) {
        const status = getImportStatus(scope);
        if (!status) {
            return;
        }

        status.textContent = message || '';
        status.classList.toggle('is-error', type === 'error');
        status.classList.toggle('is-success', type === 'success');
    }

    function extractCoordinatesFromMapsUrl(url) {
        const decodedUrl = decodeURIComponent(url);
        const patterns = [
            /@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/,
            /!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/,
            /[?&]q=(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/,
            /[?&]ll=(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/,
        ];

        for (const pattern of patterns) {
            const match = decodedUrl.match(pattern);
            if (match) {
                return {
                    lat: match[1],
                    lng: match[2],
                };
            }
        }

        return null;
    }

    function extractPlaceNameFromMapsUrl(url) {
        const decodedUrl = decodeURIComponent(url);
        const placeMatch = decodedUrl.match(/\/maps\/place\/([^/@?]+)/);

        if (placeMatch) {
            return placeMatch[1].replace(/\+/g, ' ').trim();
        }

        const queryMatch = decodedUrl.match(/[?&]query=([^&]+)/) || decodedUrl.match(/[?&]q=([^&]+)/);

        return queryMatch ? queryMatch[1].replace(/\+/g, ' ').trim() : '';
    }

    function applyResolvedAddress(scope, address) {
        const addressTarget = scope.querySelector('[data-address-target]');
        const addressSearch = getAddressSearch(scope);

        if (addressTarget && address) {
            addressTarget.value = address;
        }
        if (addressSearch && address) {
            addressSearch.value = address;
            updateMapsLink(addressSearch);
        }
    }

    async function reverseGeocodeFromServer(lat, lng) {
        const url = new URL(reverseGeocodeUrl, window.location.origin);
        url.searchParams.set('latitude', lat);
        url.searchParams.set('longitude', lng);

        const response = await fetch(url.toString(), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            return '';
        }

        const payload = await response.json();

        return payload.address || '';
    }

    async function reverseGeocode(scope, lat, lng, fallbackAddress) {
        if (!window.google || !google.maps || !google.maps.Geocoder) {
            setImportStatus(scope, 'Koordinat terisi. Mengambil alamat detail dari koordinat...', 'success');

            try {
                const serverAddress = await reverseGeocodeFromServer(lat, lng);
                const address = serverAddress || fallbackAddress;

                applyResolvedAddress(scope, address);
                setImportStatus(scope, serverAddress ? 'Alamat dan koordinat berhasil diambil dari Google Maps.' : 'Koordinat terisi. Alamat detail butuh Google Maps API key, nama lokasi dipakai sementara.', 'success');
            } catch (error) {
                applyResolvedAddress(scope, fallbackAddress);
                setImportStatus(scope, 'Koordinat terisi. Alamat detail Google belum bisa diambil, nama lokasi dipakai sementara.', 'success');
            }

            return;
        }

        const geocoder = new google.maps.Geocoder();
        geocoder.geocode({ location: { lat: Number(lat), lng: Number(lng) } }, function (results, status) {
            const address = status === 'OK' && results[0]?.formatted_address
                ? results[0].formatted_address
                : fallbackAddress;

            applyResolvedAddress(scope, address);

            setImportStatus(scope, address ? 'Alamat dan koordinat berhasil diambil dari Google Maps.' : 'Koordinat terisi. Alamat detail belum ditemukan.', 'success');
        });
    }

    function importGoogleMapsUrl(button) {
        const scope = getScope(button);
        const urlInput = scope.querySelector('.js-google-maps-url');
        const latitudeTarget = scope.querySelector('[data-latitude-target]');
        const longitudeTarget = scope.querySelector('[data-longitude-target]');
        const value = urlInput?.value?.trim();

        if (!value) {
            setImportStatus(scope, 'Tempel link Google Maps dulu, lalu klik Ambil Data.', 'error');
            return;
        }

        const coordinates = extractCoordinatesFromMapsUrl(value);
        if (!coordinates) {
            setImportStatus(scope, 'Link Maps belum memuat koordinat. Pilih tempat di Google Maps, lalu copy URL setelah pin terbuka.', 'error');
            return;
        }

        if (latitudeTarget) {
            latitudeTarget.value = coordinates.lat;
        }
        if (longitudeTarget) {
            longitudeTarget.value = coordinates.lng;
        }

        reverseGeocode(scope, coordinates.lat, coordinates.lng, extractPlaceNameFromMapsUrl(value));
    }

    window.initCustomerAddressSearch = function () {
        if (!window.google || !google.maps || !google.maps.places) {
            return;
        }

        document.querySelectorAll('.js-address-search').forEach((input) => {
            if (input.dataset.autocompleteBound === '1') {
                return;
            }

            const autocomplete = new google.maps.places.Autocomplete(input, {
                componentRestrictions: { country: 'id' },
                fields: ['formatted_address', 'geometry', 'name'],
            });

            autocomplete.addListener('place_changed', () => {
                applyPlace(input, autocomplete.getPlace());
            });

            input.dataset.autocompleteBound = '1';
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-address-search').forEach((input) => {
            input.addEventListener('input', () => updateMapsLink(input));
            updateMapsLink(input);
        });

        document.querySelectorAll('.js-import-google-maps').forEach((button) => {
            button.addEventListener('click', () => importGoogleMapsUrl(button));
        });

        window.initCustomerAddressSearch();
    });
})();
</script>

@if($mapsApiKey)
<script src="https://maps.googleapis.com/maps/api/js?key={{ $mapsApiKey }}&libraries=places&callback=initCustomerAddressSearch" async defer></script>
@endif
@endpush
@endonce
