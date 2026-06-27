@php
    $fieldStyle = 'width: 100%; height: 44px; padding: 0 0.85rem; border: 1px solid var(--k-gray-300); border-radius: 8px; background: #fff; color: var(--k-gray-900); font-family: Inter, -apple-system, BlinkMacSystemFont, Segoe UI, sans-serif; font-size: 0.86rem; font-weight: 500; line-height: 44px;';
    $labelStyle = 'display: block; font-size: var(--k-font-xs); color: var(--k-gray-600); margin-bottom: 0.35rem; font-weight: 600;';
@endphp

<form method="GET" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: flex-end; margin-bottom: 1.5rem; padding: 0.9rem; border: 1px solid #e3ebf5; border-radius: 8px; background: #f8fbff;">
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
    <button class="dms-btn dms-btn-primary" type="submit" style="height: 44px; min-width: 76px; justify-content: center;">Filter</button>
    @isset($exportType)
        <a class="dms-btn dms-btn-outline" style="height: 44px; min-width: 132px; justify-content: center;" href="{{ route('reports.export', array_merge(['type' => $exportType], request()->only(['start_date', 'end_date', 'principal_id']))) }}">
            <i class="bi bi-download"></i> Export CSV
        </a>
    @endisset
</form>
