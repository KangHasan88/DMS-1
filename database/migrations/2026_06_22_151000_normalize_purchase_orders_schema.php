<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'order_date')) {
                $table->date('order_date')->nullable()->after('po_number');
            }

            if (!Schema::hasColumn('purchase_orders', 'expected_delivery_date')) {
                $table->date('expected_delivery_date')->nullable()->after('order_date');
            }

            if (!Schema::hasColumn('purchase_orders', 'received_date')) {
                $table->date('received_date')->nullable()->after('expected_delivery_date');
            }

            if (!Schema::hasColumn('purchase_orders', 'subtotal')) {
                $table->integer('subtotal')->default(0)->after('status');
            }

            if (!Schema::hasColumn('purchase_orders', 'total')) {
                $table->integer('total')->default(0)->after('subtotal');
            }

            if (!Schema::hasColumn('purchase_orders', 'internal_notes')) {
                $table->text('internal_notes')->nullable()->after('notes');
            }

            if (!Schema::hasColumn('purchase_orders', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('internal_notes')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('purchase_orders', 'approved_by')) {
                $table->foreignId('approved_by')
                    ->nullable()
                    ->after('created_by')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('purchase_orders', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE purchase_orders MODIFY status VARCHAR(30) NOT NULL DEFAULT 'draft'");
        }

        if (Schema::hasColumn('purchase_orders', 'purchase_date')) {
            DB::table('purchase_orders')
                ->whereNull('order_date')
                ->orderBy('id')
                ->get(['id', 'purchase_date', 'created_at'])
                ->each(function ($purchaseOrder) {
                    DB::table('purchase_orders')
                        ->where('id', $purchaseOrder->id)
                        ->update([
                            'order_date' => $purchaseOrder->purchase_date
                                ?: ($purchaseOrder->created_at ? substr((string) $purchaseOrder->created_at, 0, 10) : null)
                                ?: now()->toDateString(),
                        ]);
                });
        }

        if (Schema::hasColumn('purchase_orders', 'total_amount')) {
            DB::table('purchase_orders')
                ->where(function ($query) {
                    $query->whereNull('subtotal')->orWhere('subtotal', 0);
                })
                ->update(['subtotal' => DB::raw('total_amount')]);

            DB::table('purchase_orders')
                ->where(function ($query) {
                    $query->whereNull('total')->orWhere('total', 0);
                })
                ->update(['total' => DB::raw('total_amount')]);
        }

        if (Schema::hasColumn('purchase_orders', 'completed_at')) {
            DB::table('purchase_orders')
                ->where('status', 'completed')
                ->update([
                    'status' => 'received',
                    'received_date' => DB::raw('COALESCE(received_date, completed_at)'),
                ]);
        } else {
            DB::table('purchase_orders')
                ->where('status', 'completed')
                ->update(['status' => 'received']);
        }
    }

    public function down(): void
    {
        //
    }
};
