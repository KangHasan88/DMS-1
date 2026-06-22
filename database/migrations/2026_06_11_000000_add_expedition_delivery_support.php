<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('delivery_vendors')) {
            Schema::create('delivery_vendors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_branch_id')
                    ->nullable()
                    ->constrained('company_branches')
                    ->nullOnDelete();
                $table->string('name');
                $table->string('code', 30)->nullable();
                $table->string('vendor_type', 30)->default('expedition');
                $table->string('phone')->nullable();
                $table->string('contact_person')->nullable();
                $table->string('payment_term', 30)->default('invoice');
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_branch_id', 'is_active']);
                $table->index(['vendor_type', 'is_active']);
            });
        }

        if (Schema::hasTable('deliveries')) {
            Schema::table('deliveries', function (Blueprint $table) {
                if (!Schema::hasColumn('deliveries', 'delivery_method')) {
                    $table->string('delivery_method', 20)->default('internal')->after('order_id');
                }

                if (!Schema::hasColumn('deliveries', 'delivery_vendor_id')) {
                    $table->foreignId('delivery_vendor_id')
                        ->nullable()
                        ->after('delivery_method')
                        ->constrained('delivery_vendors')
                        ->nullOnDelete();
                }

                if (!Schema::hasColumn('deliveries', 'tracking_code')) {
                    $table->string('tracking_code', 100)->nullable()->after('completed_at');
                }

                if (!Schema::hasColumn('deliveries', 'actual_shipping_cost')) {
                    $table->unsignedBigInteger('actual_shipping_cost')->default(0)->after('tracking_code');
                }

                if (!Schema::hasColumn('deliveries', 'shipping_cost_status')) {
                    $table->string('shipping_cost_status', 20)->default('not_applicable')->after('actual_shipping_cost');
                }

                if (!Schema::hasColumn('deliveries', 'vendor_invoice_number')) {
                    $table->string('vendor_invoice_number', 100)->nullable()->after('shipping_cost_status');
                }
            });

            DB::table('deliveries')
                ->whereNull('delivery_method')
                ->update(['delivery_method' => 'internal']);

            if (DB::connection()->getDriverName() === 'mysql' && Schema::hasColumn('deliveries', 'kurir_id')) {
                DB::statement('ALTER TABLE deliveries MODIFY kurir_id BIGINT UNSIGNED NULL');
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('deliveries')) {
            Schema::table('deliveries', function (Blueprint $table) {
                foreach ([
                    'vendor_invoice_number',
                    'shipping_cost_status',
                    'actual_shipping_cost',
                    'tracking_code',
                    'delivery_vendor_id',
                    'delivery_method',
                ] as $column) {
                    if (Schema::hasColumn('deliveries', $column)) {
                        if ($column === 'delivery_vendor_id') {
                            $table->dropConstrainedForeignId($column);
                        } else {
                            $table->dropColumn($column);
                        }
                    }
                }
            });
        }

        Schema::dropIfExists('delivery_vendors');
    }
};
