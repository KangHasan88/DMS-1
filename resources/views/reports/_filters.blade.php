@php
    $searchLabel = $searchLabel ?? 'Cari Data';
    $searchPlaceholder = $searchPlaceholder ?? 'Cari data...';
@endphp

<form method="GET" class="dms-toolbar" style="align-items: end;">
    <div class="dms-search-form">
        @isset($filters)
            <div style="min-width: 280px;">
                <label class="form-label">{{ $searchLabel }}</label>
                <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="{{ $searchPlaceholder }}" class="form-control">
            </div>
        @endisset
        <div>
            <label class="form-label">Start Date</label>
            <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" class="form-control">
        </div>
        <div>
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" value="{{ $endDate->toDateString() }}" class="form-control">
        </div>
        @isset($principalOptions)
            <div style="min-width: 220px;">
                <label class="form-label">Principal</label>
                <select name="principal_id" class="form-control">
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
            <div style="min-width: 200px;">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
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
            <div style="min-width: 200px;">
                <label class="form-label">Kategori</label>
                <select name="category" class="form-control">
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
            <div style="min-width: 200px;">
                <label class="form-label">Insight</label>
                <select name="insight" class="form-control">
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
                <label class="form-label">Per Halaman</label>
                <select name="per_page" class="form-control">
                    @foreach([10, 25, 50, 100] as $pageSize)
                        <option value="{{ $pageSize }}" {{ (int) ($filters['per_page'] ?? 25) === $pageSize ? 'selected' : '' }}>
                            {{ $pageSize }} data
                        </option>
                    @endforeach
                </select>
            </div>
        @endisset
        <button class="dms-btn dms-btn-primary" type="submit">Filter</button>
        @isset($exportType)
            <a class="dms-btn dms-btn-outline" href="{{ route('reports.export', array_merge(['type' => $exportType], request()->only(['start_date', 'end_date', 'principal_id', 'search', 'category', 'insight', 'status', 'per_page']))) }}">
                <i class="bi bi-download"></i> Export CSV
            </a>
        @endisset
    </div>
</form>
