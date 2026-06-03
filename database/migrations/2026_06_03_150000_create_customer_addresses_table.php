<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->enum('type', ['invoice', 'shipping', 'both'])->default('shipping');
            $table->text('address');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone', 30)->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->boolean('is_default_invoice')->default(false);
            $table->boolean('is_default_shipping')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        DB::table('customers')
            ->whereNotNull('address')
            ->where('address', '<>', '')
            ->orderBy('id')
            ->chunkById(100, function ($customers) {
                foreach ($customers as $customer) {
                    DB::table('customer_addresses')->insert([
                        'customer_id' => $customer->id,
                        'label' => 'Alamat Utama',
                        'type' => 'both',
                        'address' => $customer->address,
                        'recipient_name' => $customer->name,
                        'recipient_phone' => $customer->phone,
                        'latitude' => $customer->latitude,
                        'longitude' => $customer->longitude,
                        'is_default_invoice' => true,
                        'is_default_shipping' => true,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }, 'id');
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
