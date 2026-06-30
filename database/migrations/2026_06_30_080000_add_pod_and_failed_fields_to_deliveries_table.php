<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('deliveries', 'pod_receiver_name')) {
                $table->string('pod_receiver_name')->nullable()->after('proof_image');
            }

            if (!Schema::hasColumn('deliveries', 'pod_received_at')) {
                $table->timestamp('pod_received_at')->nullable()->after('pod_receiver_name');
            }

            if (!Schema::hasColumn('deliveries', 'failed_at')) {
                $table->timestamp('failed_at')->nullable()->after('pod_received_at');
            }

            if (!Schema::hasColumn('deliveries', 'failure_reason')) {
                $table->text('failure_reason')->nullable()->after('failed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            foreach (['failure_reason', 'failed_at', 'pod_received_at', 'pod_receiver_name'] as $column) {
                if (Schema::hasColumn('deliveries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};