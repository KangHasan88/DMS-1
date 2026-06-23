@extends('layouts.sidebar')

@section('page-title', 'Sales Coverage')
@section('breadcrumb', 'Relasi Bisnis / Sales Coverage')

@section('content')
<div class="dms-card">
    <div class="dms-section-header sales-coverage-card-header">
        <div>
            <h2>Sales Coverage Setting</h2>
            <p>Kelola area sales, assignment customer, dan take over salesman.</p>
        </div>
    </div>

    <div class="dms-toolbar">
        <form action="{{ route('sales-coverage.index') }}" method="GET" class="dms-search-form">
            @if(!$branchScopeId)
            <div class="dms-toolbar-actions" style="min-width: 220px;">
                <select name="company_branch_id" class="form-control" onchange="this.form.submit()">
                    <option value="">Semua Cabang</option>
                    @foreach($companyBranches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) request('company_branch_id') === (string) $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}{{ $branch->code ? ' - '.$branch->code : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="dms-toolbar-actions" style="min-width: 220px;">
                <select name="salesperson_id" class="form-control" onchange="this.form.submit()">
                    <option value="">Semua Sales</option>
                    @foreach($salespeople as $salesperson)
                        <option value="{{ $salesperson->id }}" {{ (string) request('salesperson_id') === (string) $salesperson->id ? 'selected' : '' }}>
                            {{ $salesperson->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="dms-toolbar-actions" style="min-width: 220px;">
                <select name="sales_territory_id" class="form-control" onchange="this.form.submit()">
                    <option value="">Semua Area</option>
                    @foreach($territories as $territory)
                        <option value="{{ $territory->id }}" {{ (string) request('sales_territory_id') === (string) $territory->id ? 'selected' : '' }}>
                            {{ $territory->code }} - {{ $territory->name }}{{ $territory->is_active ? '' : ' - nonaktif' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="dms-toolbar-actions" style="min-width: 160px;">
                <select name="per_page" class="form-control" onchange="this.form.submit()">
                    @foreach([10, 20, 50] as $size)
                        <option value="{{ $size }}" {{ (int) request('per_page', 10) === $size ? 'selected' : '' }}>{{ $size }} per halaman</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    @can('manage sales team')
    <div class="sales-coverage-grid">
        <div class="sales-coverage-panel">
            <div class="sales-coverage-panel-title">
                <i class="bi bi-arrow-left-right"></i>
                Assign / Take Over Customer
            </div>
            <form action="{{ route('sales-coverage.assignments.store') }}" method="POST" class="sales-coverage-form">
                @csrf
                <div class="sales-form-row">
                    <div>
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-control" required>
                            <option value="">Pilih customer</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">
                                    {{ $customer->name }}{{ $customer->companyBranch?->code ? ' - '.$customer->companyBranch->code : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Sales Baru</label>
                        <select name="salesperson_id" class="form-control" required>
                            <option value="">Pilih sales</option>
                            @foreach($salespeople as $salesperson)
                                <option value="{{ $salesperson->id }}">
                                    {{ $salesperson->name }}{{ $salesperson->companyBranch?->code ? ' - '.$salesperson->companyBranch->code : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="sales-form-row">
                    <div>
                        <label class="form-label">Area</label>
                        <select name="sales_territory_id" class="form-control">
                            <option value="">Tanpa area</option>
                            @foreach($activeTerritories as $territory)
                                <option value="{{ $territory->id }}">
                                    {{ $territory->code }} - {{ $territory->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Mulai Berlaku</label>
                        <input type="date" name="start_date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                </div>
                <div class="sales-form-row">
                    <div>
                        <label class="form-label">Tipe Assignment</label>
                        <select name="assignment_type" class="form-control">
                            <option value="permanent">Permanen</option>
                            <option value="temporary">Sementara</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" name="end_date" class="form-control" min="{{ now()->toDateString() }}">
                    </div>
                </div>
                <label class="form-label">Catatan</label>
                <input type="text" name="notes" class="form-control" placeholder="Contoh: take over karena sales resign">
                <div class="sales-form-actions">
                    <button type="submit" class="dms-btn dms-btn-primary sales-submit-btn">
                        <i class="bi bi-check2-circle"></i>
                        Simpan Assignment
                    </button>
                </div>
            </form>
        </div>

        <div class="sales-coverage-panel sales-territory-panel">
            <div class="sales-coverage-panel-title sales-territory-title">
                <i class="bi bi-map"></i>
                Tambah Area Sales
            </div>
            <form action="{{ route('sales-coverage.territories.store') }}" method="POST" class="sales-coverage-form">
                @csrf
                <div class="sales-form-row">
                    <div>
                        <label class="form-label">Cabang</label>
                        <select name="company_branch_id" class="form-control" required>
                            @foreach($companyBranches as $branch)
                                @continue($branchScopeId && (int) $branch->id !== (int) $branchScopeId)
                                <option value="{{ $branch->id }}" {{ (string) old('company_branch_id', $selectedBranchId) === (string) $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}{{ $branch->code ? ' - '.$branch->code : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Kode Area</label>
                        <input type="text" name="code" class="form-control" placeholder="TNG-A01" required>
                    </div>
                </div>
                <label class="form-label">Nama Area</label>
                <input type="text" name="name" class="form-control" placeholder="Area Tangerang Barat" required>
                <label class="form-label">Deskripsi</label>
                <input type="text" name="description" class="form-control" placeholder="Coverage wilayah sales">
                <div class="sales-form-actions">
                    <button type="submit" class="dms-btn sales-area-submit sales-submit-btn">
                        <i class="bi bi-plus-circle"></i>
                        Simpan Area
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endcan

    <div class="sales-coverage-summary">
        <div class="sales-summary-heading">
            <div>
                <strong>Ringkasan Area Sales</strong>
                <span>{{ $activeTerritories->count() }} area aktif</span>
            </div>
            <button type="button" class="sales-summary-toggle" data-sales-summary-toggle aria-expanded="true">
                <i class="bi bi-chevron-up"></i>
                <span>Sembunyikan</span>
            </button>
        </div>
        <table class="sales-summary-table" data-sales-summary-body>
            <thead>
                <tr>
                    <th>Kode Area</th>
                    <th>Nama Area</th>
                    <th>Customer Aktif</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activeTerritories as $territory)
                    <tr>
                        <td><span class="sales-area-code">{{ $territory->code }}</span></td>
                        <td>{{ $territory->name }}</td>
                        <td>{{ $territory->active_customers_count }} customer</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="sales-summary-empty">Belum ada area sales</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="dms-table-wrap">
        <table class="dms-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Cabang</th>
                    <th>Sales Owner</th>
                    <th>Area</th>
                    <th>Periode</th>
                    <th>Tipe</th>
                    <th>Catatan</th>
                    <th style="width: 110px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assignments as $assignment)
                <tr>
                    <td>
                        <div class="dms-strong">{{ $assignment->customer?->name }}</div>
                        <div class="dms-muted">{{ $assignment->customer?->phone }}</div>
                    </td>
                    <td>{{ $assignment->companyBranch?->code ?? '-' }}</td>
                    <td>
                        <div class="dms-strong">{{ $assignment->salesperson?->name }}</div>
                        <div class="dms-muted">{{ $assignment->salesperson?->companyBranch?->code ?? 'Global' }}</div>
                    </td>
                    <td>
                        @if($assignment->salesTerritory)
                            <span class="dms-badge dms-badge-info">{{ $assignment->salesTerritory->code }}</span>
                            <div class="dms-muted">
                                {{ $assignment->salesTerritory->name }}{{ $assignment->salesTerritory->is_active ? '' : ' - nonaktif' }}
                            </div>
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        {{ $assignment->start_date?->format('d M Y') }}
                        <div class="dms-muted">s/d {{ $assignment->end_date?->format('d M Y') ?? 'aktif' }}</div>
                    </td>
                    <td>
                        <span class="dms-badge {{ $assignment->assignment_type === 'temporary' ? 'dms-badge-warning' : 'dms-badge-success' }}">
                            {{ $assignment->assignment_type === 'temporary' ? 'Sementara' : 'Permanen' }}
                        </span>
                    </td>
                    <td>{{ $assignment->notes ? Str::limit($assignment->notes, 45) : '-' }}</td>
                    <td>
                        @can('manage sales team')
                        <div class="sales-row-actions">
                            <button type="button" class="dms-btn dms-btn-outline dms-btn-sm" title="Edit detail assignment" data-edit-assignment="{{ $assignment->id }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="{{ route('sales-coverage.assignments.destroy', $assignment) }}" method="POST" onsubmit="return confirm('Tutup assignment customer ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dms-btn dms-btn-outline dms-btn-sm" title="Tutup assignment">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </form>
                        </div>
                        @endcan
                    </td>
                </tr>
                @can('manage sales team')
                <tr class="sales-edit-row" id="assignment-edit-{{ $assignment->id }}" hidden>
                    <td colspan="8">
                        <form action="{{ route('sales-coverage.assignments.update', $assignment) }}" method="POST" class="sales-assignment-edit">
                            @csrf
                            @method('PUT')
                            <div class="sales-edit-summary">
                                <strong>{{ $assignment->customer?->name }}</strong>
                                <span>{{ $assignment->salesperson?->name }}</span>
                                <em>Sales owner dan customer tidak diedit di sini. Gunakan Take Over untuk pindah sales.</em>
                            </div>
                            <div class="sales-form-row">
                                <div>
                                    <label class="form-label">Area</label>
                                    <select name="sales_territory_id" class="form-control">
                                        <option value="">Tanpa area</option>
                                        @foreach($territories as $territory)
                                            @continue((int) $territory->company_branch_id !== (int) $assignment->company_branch_id)
                                            <option value="{{ $territory->id }}" {{ (string) $assignment->sales_territory_id === (string) $territory->id ? 'selected' : '' }}>
                                                {{ $territory->code }} - {{ $territory->name }}{{ $territory->is_active ? '' : ' - nonaktif' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($assignment->salesTerritory && !$assignment->salesTerritory->is_active)
                                        <small class="dms-form-help" style="color: var(--k-orange); font-weight: 600;">Area sales ini sedang nonaktif, tapi tetap ditampilkan karena masih tersimpan di assignment ini.</small>
                                    @endif
                                </div>
                                <div>
                                    <label class="form-label">Tipe Assignment</label>
                                    <select name="assignment_type" class="form-control">
                                        <option value="permanent" {{ $assignment->assignment_type === 'permanent' ? 'selected' : '' }}>Permanen</option>
                                        <option value="temporary" {{ $assignment->assignment_type === 'temporary' ? 'selected' : '' }}>Sementara</option>
                                    </select>
                                </div>
                            </div>
                            <div class="sales-form-row">
                                <div>
                                    <label class="form-label">Sampai Tanggal</label>
                                    <input type="date" name="end_date" class="form-control" value="{{ $assignment->end_date?->toDateString() }}" min="{{ now()->toDateString() }}">
                                </div>
                                <div>
                                    <label class="form-label">Catatan</label>
                                    <input type="text" name="notes" class="form-control" value="{{ $assignment->notes }}" placeholder="Catatan perubahan assignment">
                                </div>
                            </div>
                            <div class="sales-form-actions">
                                <button type="button" class="dms-btn dms-btn-outline" data-close-assignment-edit="{{ $assignment->id }}">
                                    Batal
                                </button>
                                <button type="submit" class="dms-btn dms-btn-primary">
                                    <i class="bi bi-save"></i>
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </td>
                </tr>
                @endcan
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 3rem;">
                        <i class="bi bi-diagram-3" style="font-size: 3rem; color: var(--k-gray-300);"></i>
                        <p style="margin-top: 1rem; color: var(--k-gray-500);">Belum ada assignment customer-sales aktif</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="dms-pagination">
        <div class="dms-pagination-summary">
            Menampilkan {{ $assignments->firstItem() ?? 0 }} - {{ $assignments->lastItem() ?? 0 }} dari {{ $assignments->total() }} assignment
        </div>
        <div>{{ $assignments->withQueryString()->links() }}</div>
    </div>
</div>

<style>
.sales-coverage-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 0.82fr);
    gap: 1rem;
    margin-bottom: 1rem;
}
.sales-coverage-card-header {
    margin-bottom: 1rem;
}
.sales-coverage-card-header h2 {
    font-size: 1.05rem;
    line-height: 1.3;
    font-weight: 650;
    color: var(--k-gray-900);
}
.sales-coverage-card-header p {
    margin-top: 0.18rem;
    color: var(--k-gray-500);
    font-size: 0.86rem;
}
.sales-coverage-panel {
    border: 1px solid var(--k-gray-200);
    border-radius: 8px;
    padding: 1rem;
    background: var(--k-white);
}
.sales-coverage-panel-title {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    color: var(--k-gray-800);
    font-size: 0.95rem;
    font-weight: 650;
    margin-bottom: 0.85rem;
}
.sales-coverage-panel .form-label {
    font-size: 0.8rem;
    font-weight: 650;
    color: var(--k-gray-700);
}
.sales-coverage-form {
    display: grid;
    gap: 0.75rem;
}
.sales-form-row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
}
.sales-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 0.2rem;
}
.sales-submit-btn {
    width: auto;
    min-width: 168px;
    justify-content: center;
    padding-left: 1.05rem;
    padding-right: 1.05rem;
}
.sales-row-actions {
    display: flex;
    align-items: center;
    gap: 0.45rem;
}
.sales-edit-row td {
    background: #f8fbff;
    border-top: 0;
}
.sales-assignment-edit {
    display: grid;
    gap: 0.75rem;
    padding: 0.9rem;
    border: 1px solid var(--k-gray-200);
    border-radius: 8px;
    background: var(--k-white);
}
.sales-edit-summary {
    display: flex;
    align-items: baseline;
    gap: 0.8rem;
    flex-wrap: wrap;
    color: var(--k-gray-800);
}
.sales-edit-summary span {
    color: var(--k-blue);
    font-weight: 700;
}
.sales-edit-summary em {
    font-style: normal;
    color: var(--k-gray-500);
    font-size: 0.78rem;
}
.sales-territory-panel {
    border-color: #bfdbfe;
    background: #f8fbff;
}
.sales-territory-title {
    color: #1e40af;
}
.sales-territory-title i {
    color: #2563eb;
}
.sales-area-submit {
    background: #1d4ed8;
    border-color: #1d4ed8;
    color: #fff;
}
.sales-area-submit:hover {
    background: #1e40af;
    border-color: #1e40af;
    color: #fff;
}
.sales-coverage-summary {
    border: 1px solid var(--k-gray-200);
    border-radius: 8px;
    background: var(--k-gray-50);
    margin: 0.75rem 0 1rem;
    overflow: hidden;
}
.sales-summary-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.68rem 0.85rem;
    border-bottom: 1px solid var(--k-gray-200);
    background: var(--k-white);
}
.sales-summary-heading strong {
    display: block;
    color: var(--k-gray-800);
    font-size: 0.84rem;
    font-weight: 650;
}
.sales-summary-heading span {
    color: var(--k-gray-500);
    font-size: 0.74rem;
}
.sales-summary-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    min-height: 32px;
    padding: 0.35rem 0.65rem;
    border: 1px solid var(--k-gray-200);
    border-radius: 7px;
    background: var(--k-white);
    color: var(--k-blue);
    font-size: 0.75rem;
    font-weight: 650;
    cursor: pointer;
}
.sales-summary-toggle:hover {
    border-color: var(--k-blue-light);
    background: #f8fbff;
}
.sales-summary-toggle i {
    font-size: 0.85rem;
}
.sales-coverage-summary.is-collapsed .sales-summary-table {
    display: none;
}
.sales-summary-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
.sales-summary-table th,
.sales-summary-table td {
    padding: 0.62rem 0.85rem;
    border-bottom: 1px solid var(--k-gray-200);
    text-align: left;
    vertical-align: middle;
}
.sales-summary-table th {
    background: #f8fafc;
    color: var(--k-gray-500);
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
}
.sales-summary-table td {
    color: var(--k-gray-700);
    font-size: 0.8rem;
}
.sales-summary-table tr:last-child td {
    border-bottom: 0;
}
.sales-summary-table th:first-child,
.sales-summary-table td:first-child {
    width: 150px;
}
.sales-summary-table th:last-child,
.sales-summary-table td:last-child {
    width: 150px;
}
.sales-area-code {
    color: var(--k-blue);
    font-weight: 700;
}
.sales-summary-empty {
    padding: 0.85rem;
    color: var(--k-gray-500);
    font-size: 0.8rem;
}
@media (max-width: 960px) {
    .sales-coverage-grid,
    .sales-form-row {
        grid-template-columns: 1fr;
    }
    .sales-submit-btn {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const summary = document.querySelector('.sales-coverage-summary');
    const summaryToggle = document.querySelector('[data-sales-summary-toggle]');
    const summaryToggleIcon = summaryToggle ? summaryToggle.querySelector('i') : null;
    const summaryToggleText = summaryToggle ? summaryToggle.querySelector('span') : null;
    const summaryStorageKey = 'dms.salesCoverage.summaryCollapsed';

    function applySummaryState(isCollapsed) {
        if (!summary || !summaryToggle || !summaryToggleIcon || !summaryToggleText) {
            return;
        }

        summary.classList.toggle('is-collapsed', isCollapsed);
        summaryToggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
        summaryToggleIcon.className = isCollapsed ? 'bi bi-chevron-down' : 'bi bi-chevron-up';
        summaryToggleText.textContent = isCollapsed ? 'Tampilkan' : 'Sembunyikan';
    }

    if (summaryToggle) {
        applySummaryState(localStorage.getItem(summaryStorageKey) === '1');
        summaryToggle.addEventListener('click', function () {
            const nextCollapsed = ! summary.classList.contains('is-collapsed');
            localStorage.setItem(summaryStorageKey, nextCollapsed ? '1' : '0');
            applySummaryState(nextCollapsed);
        });
    }

    document.querySelectorAll('[data-edit-assignment]').forEach(function (button) {
        button.addEventListener('click', function () {
            const row = document.getElementById('assignment-edit-' + button.dataset.editAssignment);
            if (row) {
                row.hidden = ! row.hidden;
            }
        });
    });

    document.querySelectorAll('[data-close-assignment-edit]').forEach(function (button) {
        button.addEventListener('click', function () {
            const row = document.getElementById('assignment-edit-' + button.dataset.closeAssignmentEdit);
            if (row) {
                row.hidden = true;
            }
        });
    });
});
</script>
@endsection
