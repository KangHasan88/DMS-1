<?php

use App\Models\Order;
use App\Services\Saas\RemoteModuleProvisioningSigner;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

Artisan::command('orders:sync-legacy-stock {--dry-run : Simulate syncing without saving changes}', function () {
    $query = Order::query()
        ->where('fulfillment_type', Order::FULFILLMENT_STOCK)
        ->whereNotIn('status', [Order::STATUS_PENDING_PAYMENT, Order::STATUS_CANCELLED])
        ->whereHas('items', function ($itemQuery) {
            $itemQuery->where('fulfillment_status', '!=', 'fulfilled');
        })
        ->with(['items.product.stock']);

    $total = (clone $query)->count();
    $processed = 0;
    $warnings = 0;
    $dryRun = (bool) $this->option('dry-run');

    $this->info('Target order stock sync: ' . $total);
    if ($dryRun) {
        $this->line('Running in dry-run mode. No data will be changed.');
    }

    $query->orderBy('id')->chunkById(20, function ($orders) use (&$processed, &$warnings, $dryRun) {
        foreach ($orders as $order) {
            if ($dryRun) {
                $processed++;
                $this->line("[DRY] {$order->order_number} ({$order->status})");
                continue;
            }

            DB::transaction(function () use ($order, &$warnings) {
                $allAvailable = $order->processStockReduction();

                if (!$allAvailable) {
                    $warnings++;
                }

                if ($order->status === Order::STATUS_CHECKING_STOCK && $allAvailable) {
                    $order->updateStatus(Order::STATUS_PICKING, 'Stok dialokasikan, picking dimulai');
                } elseif ($order->status === Order::STATUS_PROCURING && $allAvailable && !$order->requiresPacking()) {
                    $order->updateStatus(Order::STATUS_READY, 'Barang siap dikirim tanpa packing/repack');
                }
            });

            $processed++;
            $this->line("[OK] {$order->order_number} diselaraskan");
        }
    });

    if ($dryRun) {
        $this->info("Dry-run selesai. {$processed} order akan diselaraskan.");
        return;
    }

    $this->info("Sinkronisasi selesai. {$processed} order diproses.");
    if ($warnings > 0) {
        $this->warn("{$warnings} order masih kekurangan stock saat sinkronisasi.");
    }
})->purpose('Sync legacy stock orders so old records follow the current stock flow.');

Artisan::command('saas:health-callback {--send : Send payload to central callback URL}', function (RemoteModuleProvisioningSigner $signer) {
    $checkedAt = now()->toIso8601String();
    $payload = $signer->signHealth([
        'module_key' => config('modules.key', 'dms'),
        'status' => 'healthy',
        'message' => 'DMS module is reachable.',
        'checked_at' => $checkedAt,
        'expires' => now()->addSeconds((int) config('modules.remote_provision_ttl_seconds', 300))->getTimestamp(),
    ]);

    $url = config('modules.central_health_callback_url');

    if (!$this->option('send')) {
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info('Dry-run selesai. Tambahkan --send untuk POST ke central callback URL.');

        return self::SUCCESS;
    }

    if (!$url) {
        $this->error('MODULE_CENTRAL_HEALTH_CALLBACK_URL belum dikonfigurasi.');

        return self::FAILURE;
    }

    $response = Http::asJson()
        ->timeout(10)
        ->post($url, $payload);

    if ($response->successful()) {
        $this->info('Health callback terkirim: HTTP '.$response->status());
        $this->line($response->body());

        return self::SUCCESS;
    }

    $this->error('Health callback gagal: HTTP '.$response->status());
    $this->line($response->body());

    return self::FAILURE;
})->purpose('Build or send signed SaaS health callback payload for the central registry.');
