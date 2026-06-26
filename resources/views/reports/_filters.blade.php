<form method="GET" style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: end; margin-bottom: 1.5rem;">
    <div>
        <label style="display: block; font-size: 0.75rem; color: var(--k-gray-500); margin-bottom: 0.35rem;">Start Date</label>
        <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" style="padding: 0.65rem 0.75rem; border: 1px solid var(--k-gray-300); border-radius: 8px;">
    </div>
    <div>
        <label style="display: block; font-size: 0.75rem; color: var(--k-gray-500); margin-bottom: 0.35rem;">End Date</label>
        <input type="date" name="end_date" value="{{ $endDate->toDateString() }}" style="padding: 0.65rem 0.75rem; border: 1px solid var(--k-gray-300); border-radius: 8px;">
    </div>
    @isset($principalOptions)
        <div>
            <label style="display: block; font-size: 0.75rem; color: var(--k-gray-500); margin-bottom: 0.35rem;">Principal</label>
            <select name="principal_id" style="min-width: 220px; padding: 0.65rem 0.75rem; border: 1px solid var(--k-gray-300); border-radius: 8px;">
                <option value="">Semua Principal</option>
                @foreach($principalOptions as $principal)
                    <option value="{{ $principal->id }}" {{ (string) ($selectedPrincipalId ?? '') === (string) $principal->id ? 'selected' : '' }}>
                        {{ $principal->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endisset
    <button class="dms-btn dms-btn-primary" type="submit">Filter</button>
    @isset($exportType)
        <a class="dms-btn dms-btn-outline" href="{{ route('reports.export', array_merge(['type' => $exportType], request()->only(['start_date', 'end_date', 'principal_id']))) }}">
            <i class="bi bi-download"></i> Export CSV
        </a>
    @endisset
</form>
