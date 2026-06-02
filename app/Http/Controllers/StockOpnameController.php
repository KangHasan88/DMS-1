<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockOpnameController extends Controller
{
    public function index()
    {
        $stockOpnames = StockOpname::with('createdBy', 'completedBy')
            ->withCount('items')
            ->latest()
            ->paginate(10);

        return view('stock-opnames.index', compact('stockOpnames'));
    }

    public function create()
    {
        $activeProductsCount = Product::active()->count();

        return view('stock-opnames.create', compact('activeProductsCount'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'opname_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated) {
            $stockOpname = StockOpname::create([
                'opname_number' => StockOpname::generateNumber(),
                'opname_date' => $validated['opname_date'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            Product::with('stock')
                ->active()
                ->orderBy('name')
                ->get()
                ->each(function (Product $product) use ($stockOpname) {
                    StockOpnameItem::create([
                        'stock_opname_id' => $stockOpname->id,
                        'product_id' => $product->id,
                        'system_quantity' => $product->stock?->quantity ?? 0,
                    ]);
                });
        });

        return redirect()->route('stock-opnames.index')
            ->with('success', 'Dokumen stock opname berhasil dibuat.');
    }

    public function show(StockOpname $stockOpname)
    {
        $stockOpname->load('createdBy', 'completedBy', 'items.product.unit');

        return view('stock-opnames.show', compact('stockOpname'));
    }

    public function update(Request $request, StockOpname $stockOpname)
    {
        if (! $stockOpname->isDraft()) {
            return back()->with('error', 'Stock opname yang sudah selesai tidak dapat diubah.');
        }

        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'exists:stock_opname_items,id'],
            'items.*.counted_quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($stockOpname, $validated) {
            foreach ($validated['items'] as $itemData) {
                $item = $stockOpname->items()->whereKey($itemData['id'])->firstOrFail();
                $item->counted_quantity = $itemData['counted_quantity'] ?? null;
                $item->notes = $itemData['notes'] ?? null;
                $item->recountDifference();
                $item->save();
            }
        });

        return redirect()->route('stock-opnames.show', $stockOpname)
            ->with('success', 'Hasil hitung fisik berhasil disimpan.');
    }

    public function complete(StockOpname $stockOpname)
    {
        if (! $stockOpname->isDraft()) {
            return back()->with('error', 'Stock opname sudah selesai.');
        }

        $stockOpname->load('items.product.stock');

        if ($stockOpname->items->contains(fn (StockOpnameItem $item) => is_null($item->counted_quantity))) {
            return back()->with('error', 'Lengkapi stok fisik semua produk sebelum menyelesaikan opname.');
        }

        DB::transaction(function () use ($stockOpname) {
            foreach ($stockOpname->items as $item) {
                $stock = $item->product->stock ?: ProductStock::create([
                    'product_id' => $item->product_id,
                    'quantity' => 0,
                ]);

                $before = $stock->quantity;
                $after = $item->counted_quantity;
                $difference = $after - $before;

                $item->system_quantity = $before;
                $item->difference_quantity = $difference;
                $item->save();

                if ($difference === 0) {
                    continue;
                }

                $stock->update([
                    'quantity' => $after,
                    'last_updated_at' => now(),
                    'updated_by' => Auth::id(),
                ]);

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'source_type' => StockMovement::SOURCE_ADJUSTMENT,
                    'source_id' => $stockOpname->id,
                    'type' => StockMovement::TYPE_ADJUSTMENT,
                    'quantity' => abs($difference),
                    'before_quantity' => $before,
                    'after_quantity' => $after,
                    'reason' => 'Stock opname #' . $stockOpname->opname_number,
                    'created_by' => Auth::id(),
                ]);
            }

            $stockOpname->update([
                'status' => StockOpname::STATUS_COMPLETED,
                'completed_by' => Auth::id(),
                'completed_at' => now(),
            ]);
        });

        return redirect()->route('stock-opnames.show', $stockOpname)
            ->with('success', 'Stock opname selesai dan stok sistem sudah disesuaikan.');
    }
}
