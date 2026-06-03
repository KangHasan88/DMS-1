<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('invoice_address_id')->nullable()->after('user_id')->constrained('customer_addresses')->nullOnDelete();
            $table->foreignId('shipping_address_id')->nullable()->after('invoice_address_id')->constrained('customer_addresses')->nullOnDelete();
            $table->text('invoice_address_snapshot')->nullable()->after('address');
            $table->text('shipping_address_snapshot')->nullable()->after('invoice_address_snapshot');
            $table->string('shipping_recipient_name')->nullable()->after('shipping_address_snapshot');
            $table->string('shipping_recipient_phone', 30)->nullable()->after('shipping_recipient_name');
            $table->boolean('shipping_same_as_invoice')->default(false)->after('shipping_recipient_phone');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoice_address_id');
            $table->dropConstrainedForeignId('shipping_address_id');
            $table->dropColumn([
                'invoice_address_snapshot',
                'shipping_address_snapshot',
                'shipping_recipient_name',
                'shipping_recipient_phone',
                'shipping_same_as_invoice',
            ]);
        });
    }
};
