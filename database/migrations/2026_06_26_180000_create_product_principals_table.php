<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_principals', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('principal_id')
                ->nullable()
                ->after('id')
                ->constrained('product_principals')
                ->nullOnDelete();
        });

        $now = now();
        $principals = [
            ['id' => 1, 'code' => 'UNILEVER', 'name' => 'Unilever', 'sort_order' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'code' => 'INDOFOOD', 'name' => 'Indofood', 'sort_order' => 2, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'code' => 'DANONE', 'name' => 'Danone', 'sort_order' => 3, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('product_principals')->insert($principals);

        $categories = [
            'Home Care',
            'Personal Care',
            'Makanan Instan',
            'Cooking Essentials',
            'Bumbu & Saus',
            'Minuman',
            'Returnable Packaging',
        ];

        foreach ($categories as $index => $name) {
            DB::table('product_categories')->updateOrInsert(
                ['name' => $name],
                [
                    'slug' => Str::slug($name),
                    'is_active' => true,
                    'sort_order' => $index + 1,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $productUpdates = [
            1 => ['principal_id' => 1, 'name' => 'Rinso Deterjen Bubuk 1.8kg', 'category' => 'Home Care'],
            2 => ['principal_id' => 1, 'name' => 'Sunlight Jeruk Nipis 755ml', 'category' => 'Home Care'],
            3 => ['principal_id' => 1, 'name' => 'Lifebuoy Body Wash 450ml', 'category' => 'Personal Care'],
            4 => ['principal_id' => 1, 'name' => 'Pepsodent Pasta Gigi 190g', 'category' => 'Personal Care'],
            5 => ['principal_id' => 2, 'name' => 'Indomie Goreng Dus 40 pcs', 'category' => 'Makanan Instan'],
            6 => ['principal_id' => 2, 'name' => 'Pop Mie Ayam Dus 24 pcs', 'category' => 'Makanan Instan'],
            7 => ['principal_id' => 2, 'name' => 'Bimoli Minyak Goreng 2L', 'category' => 'Cooking Essentials'],
            8 => ['principal_id' => 2, 'name' => 'Sambal Pedas 335ml', 'category' => 'Bumbu & Saus'],
            9 => ['principal_id' => 3, 'name' => 'Mizone Lychee Lemon 500ml', 'category' => 'Minuman'],
            10 => ['principal_id' => 3, 'name' => 'VIT Air Mineral 1500ml', 'category' => 'Minuman'],
            132 => ['principal_id' => 3, 'name' => 'AQUA Galon 19L', 'category' => 'Returnable Packaging'],
            136 => ['principal_id' => 3, 'name' => 'AQUA Botol 600ml Dus 24 pcs', 'category' => 'Minuman'],
        ];

        foreach ($productUpdates as $id => $data) {
            Product::whereKey($id)->update($data);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('principal_id');
        });

        Schema::dropIfExists('product_principals');
    }
};
