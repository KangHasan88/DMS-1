@php
    $fieldStyle = 'width: 100%; height: 44px; padding: 0 0.85rem; border: 1px solid var(--k-gray-300); border-radius: 8px; background: #fff; color: var(--k-gray-900); font-family: Inter, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif; font-size: 0.86rem; font-weight: 500; line-height: 44px;';
    $labelStyle = 'display: block; font-size: var(--k-font-xs); color: var(--k-gray-600); margin-bottom: 0.35rem; font-weight: 600;';
    $searchLabel = $searchLabel ?? 'Cari Data';
    $searchPlaceholder = $searchPlaceholder ?? 'Cari data...';
@endphp

<form method="GET" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: flex-end; margin-bottom: 1.5rem; padding: 0.9rem; border: 1px solid #e3ebf5; border-radius: 8px; background: #f8fbff;">
    @isset($filters)
        <div style="min-width: 280px; flex: 1 1 280px;">
            <label style="{{ $labelStyle }}">{{ $searchLabel }}</label>
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ $searchPlaceholder }}" style="{{ $fieldStyle }}">
        </div>
    @endisset
    <div style="min-width: 190px;">
        <label style="{{ $labelStyle }}">Start Date</label>
        <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" style="{{ $fieldStyle }}">
    </div>
    <div style="min-width: 190px;">
        <label style="{{ $labelStyle }}">End Date</label>
        <input type="date" name="end_date" value="{{ $endDate->toDateString() }}" style="{{ $fieldStyle }}">
    </div>
    @isset($principalOptions)
        <div style="min-width: 250px;">
            <label style="{{ $labelStyle }}">Principal</label>
            <select name="principal_id" style="{{ $fieldStyle }} min-width: 250px; appearance: auto;">
                <option value="">Semua Principal</option>
                @foreach($principalOptions as $principal)
                    <option value="{{ $principal->id }}" {{ (string) ($selectedPrincipalId ?? '') === (string) $principal->id ? 'selected' : '' }}>
                        {{ $principal->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endisset
    @isset($statusOptions)
        <div style="min-width: 220px;">
            <label style="{{ $labelStyle }}">Status</label>
            <select name="status" style="{{ $fieldStyle }} min-width: 220px; appearance: auto;">
                <option value="">Semua Status</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" {{ (string) ($filters['status'] ?? '') === (string) $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
    @endisset
    @isset($categoryOptions)
        <div style="min-width: 220px;">
            <label style="{{ $labelStyle }}">Kategori</label>
            <select name="category" style="{{ $fieldStyle }} min-width: 220px; appearance: auto;">
                <option value="">Semua Kategori</option>
                @foreach($categoryOptions as $category)
                    <option value="{{ $category }}" {{ (string) ($filters['category'] ?? '') === (string) $category ? 'selected' : '' }}>
                        {{ $category }}
                    </option>
                @endforeach
            </select>
        </div>
    @endisset
    @isset($insightOptions)
        <div style="min-width: 220px;">
            <label style="{{ $labelStyle }}">Insight</label>
            <select name="insight" style="{{ $fieldStyle }} min-width: 220px; appearance: auto;">
                <option value="">Semua Insight</option>
                @foreach($insightOptions as $value => $label)
                    <option value="{{ $value }}" {{ (string) ($filters['insight'] ?? '') === (string) $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
    @endisset
    @isset($filters)
        <div style="min-width: 150px;">
            <label style="{{ $labelStyle }}">Per Halaman</label>
            <select name="per_page" style="{{ $fieldStyle }} min-width: 150px; appearance: auto;">
                @foreach([10, 25, 50, 100] as $pageSize)
                    <option value="{{ $pageSize }}" {{ (int) ($filters['per_page'] ?? 25) === $pageSize ? 'selected' : '' }}>
                        {{ $pageSize }} data
                    </option>
                @endforeach
            </select>
        </div>
    @endisset
    <button class="dms-btn dms-btn-primary" type="submit" style="height: 44px; min-width: 76px; justify-content: center;">Filter</button>
    @isset($exportType)
        <a class="dms-btn dms-btn-outline" style="height: 44px; min-width: 132px; justify-content: center;" href="{{ route('reports.export', array_merge(['type' => $exportType], request()->only(['start_date', 'end_date', 'principal_id', 'search', 'category', 'insight', 'status', 'per_page']))) }}">
            <i class="bi bi-download"></i> Export CSV
        </a>
    @endisset
</form>
