<?php

namespace App\Http\Controllers;

use App\Models\CompanyBranch;
use App\Models\InventoryDocument;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryDocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryDocument::with('warehouse', 'companyBranch', 'creator')
            ->withCount('items');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($query) use ($search) {
                $query->where('document_number', 'like', '%'.$search.'%')
                    ->orWhere('reference_number', 'like', '%'.$search.'%')
                    ->orWhereHas('warehouse', fn ($warehouseQuery) => $warehouseQuery->where('name', 'like', '%'.$search.'%'));
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        $documents = $query->orderByDesc('document_date')
            ->orderByDesc('id')
            ->paginate($request->get('per_page', 20));

        return view('inventory-documents.index', [
            'documents' => $documents,
            'types' => InventoryDocument::TYPES,
            'statuses' => InventoryDocument::STATUSES,
            'warehouses' => Warehouse::active()->orderByDesc('is_default')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        return view('inventory-documents.create', [
            'types' => InventoryDocument::TYPES,
            'warehouses' => Warehouse::active()->orderByDesc('is_default')->orderBy('sort_order')->orderBy('name')->get(),
            'branches' => CompanyBranch::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'products' => Product::with('unit', 'principal')->active()->orderBy('name')->get(),
            'selectedType' => $request->get('type', InventoryDocument::TYPE_BTB),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(InventoryDocument::TYPES))],
            'document_date' => ['required', 'date'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'company_branch_id' => ['nullable', 'exists:company_branches,id'],
            'reference_number' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['nullable', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $document = DB::transaction(function () use ($validated) {
            $document = InventoryDocument::create([
                'document_number' => InventoryDocument::nextDocumentNumber($validated['type']),
                'type' => $validated['type'],
                'status' => InventoryDocument::STATUS_DRAFT,
                'document_date' => $validated['document_date'],
                'warehouse_id' => $validated['warehouse_id'],
                'company_branch_id' => $validated['company_branch_id'] ?? null,
                'reference_number' => $validated['reference_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                $document->items()->create($item);
            }

            return $document;
        });

        return redirect()->route('inventory-documents.show', $document)
            ->with('success', 'Dokumen stok berhasil dibuat. Review lalu posting jika sudah benar.');
    }

    public function show(InventoryDocument $inventoryDocument)
    {
        $inventoryDocument->load('warehouse', 'companyBranch', 'items.product.unit', 'creator', 'postedBy', 'voidedBy');

        return view('inventory-documents.show', ['document' => $inventoryDocument]);
    }

    public function post(InventoryDocument $inventoryDocument)
    {
        try {
            $inventoryDocument->post(Auth::user());
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal posting dokumen: '.$e->getMessage());
        }

        return redirect()->route('inventory-documents.show', $inventoryDocument)
            ->with('success', 'Dokumen berhasil diposting dan stok sudah diperbarui.');
    }

    public function void(Request $request, InventoryDocument $inventoryDocument)
    {
        $validated = $request->validate([
            'void_reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $inventoryDocument->void($validated['void_reason'], Auth::user());
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal void dokumen: '.$e->getMessage());
        }

        return redirect()->route('inventory-documents.show', $inventoryDocument)
            ->with('success', 'Dokumen berhasil divoid dan reversal stok sudah dicatat.');
    }
}
