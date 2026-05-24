<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('email');
            $table->string('phone')->nullable()->after('username');
            $table->string('photo')->nullable()->after('phone');
            $table->enum('gender', ['male', 'female'])->nullable()->after('photo');
            $table->date('birth_date')->nullable()->after('gender');
            $table->text('address')->nullable()->after('birth_date');
            $table->boolean('is_active')->default(true)->after('address');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->string('last_login_ip')->nullable()->after('last_login_at');
            $table->string('employee_id')->nullable()->unique()->after('last_login_ip');
            $table->string('position')->nullable()->after('employee_id');
            $table->string('department')->nullable()->after('position');
            $table->date('join_date')->nullable()->after('department');
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->after('join_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['supervisor_id']);
            
            // Then drop columns
            $table->dropColumn([
                'username',
                'phone',
                'photo',
                'gender',
                'birth_date',
                'address',
                'is_active',
                'last_login_at',
                'last_login_ip',
                'employee_id',
                'position',
                'department',
                'join_date',
                'supervisor_id'
            ]);
        });
    }
};