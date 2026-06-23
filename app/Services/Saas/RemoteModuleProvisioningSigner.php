<?php

namespace App\Services\Saas;

use Illuminate\Support\Arr;

class RemoteModuleProvisioningSigner
{
    public function sign(array $payload): array
    {
        $payload = $this->canonicalPayload($payload);
        $payload['signature'] = hash_hmac('sha256', http_build_query($payload), $this->secret());

        return $payload;
    }

    public function verify(array $payload): bool
    {
        $signature = (string) ($payload['signature'] ?? '');

        if ($signature === '') {
            return false;
        }

        $payload = $this->canonicalPayload(Arr::except($payload, ['signature']));

        if ($payload['module_key'] !== (string) config('modules.key', 'dms')) {
            return false;
        }

        if ((int) $payload['expires'] < now()->getTimestamp()) {
            return false;
        }

        $expected = hash_hmac('sha256', http_build_query($payload), $this->secret());

        return hash_equals($expected, $signature);
    }

    public function signCallback(array $payload): array
    {
        $payload = $this->canonicalCallbackPayload($payload);
        $payload['signature'] = hash_hmac('sha256', http_build_query($payload), $this->secret());

        return $payload;
    }

    public function signHealth(array $payload): array
    {
        $payload = $this->canonicalHealthPayload($payload);
        $payload['signature'] = hash_hmac('sha256', http_build_query($payload), $this->secret());

        return $payload;
    }

    public function callbackPayload(array $payload, string $status = 'succeeded', ?string $message = null): array
    {
        return $this->signCallback([
            'tenant_id' => $payload['tenant_id'] ?? '',
            'tenant_module_id' => $payload['tenant_module_id'] ?? '',
            'module_key' => $payload['module_key'] ?? '',
            'operation_id' => $payload['operation_id'] ?? '',
            'status' => $status,
            'message' => $message ?: 'DMS remote module provisioned.',
            'remote_database_ref' => 'dms:tenant:'.($payload['tenant_id'] ?? ''),
            'remote_storage_ref' => 'dms:tenant:'.($payload['tenant_id'] ?? ''),
            'expires' => now()->addSeconds($this->ttl())->getTimestamp(),
        ]);
    }

    private function canonicalPayload(array $payload): array
    {
        return [
            'tenant_id' => (string) ($payload['tenant_id'] ?? ''),
            'tenant_module_id' => (string) ($payload['tenant_module_id'] ?? ''),
            'module_key' => (string) ($payload['module_key'] ?? ''),
            'operation_id' => (string) ($payload['operation_id'] ?? ''),
            'expires' => (int) ($payload['expires'] ?? 0),
        ];
    }

    private function canonicalCallbackPayload(array $payload): array
    {
        return [
            'tenant_id' => (string) ($payload['tenant_id'] ?? ''),
            'tenant_module_id' => (string) ($payload['tenant_module_id'] ?? ''),
            'module_key' => (string) ($payload['module_key'] ?? ''),
            'operation_id' => (string) ($payload['operation_id'] ?? ''),
            'status' => (string) ($payload['status'] ?? ''),
            'message' => (string) ($payload['message'] ?? ''),
            'remote_database_ref' => (string) ($payload['remote_database_ref'] ?? ''),
            'remote_storage_ref' => (string) ($payload['remote_storage_ref'] ?? ''),
            'expires' => (int) ($payload['expires'] ?? 0),
        ];
    }

    private function canonicalHealthPayload(array $payload): array
    {
        return [
            'module_key' => (string) ($payload['module_key'] ?? ''),
            'status' => (string) ($payload['status'] ?? ''),
            'message' => (string) ($payload['message'] ?? ''),
            'checked_at' => (string) ($payload['checked_at'] ?? ''),
            'expires' => (int) ($payload['expires'] ?? 0),
        ];
    }

    private function ttl(): int
    {
        return (int) config('modules.remote_provision_ttl_seconds', 300);
    }

    private function secret(): string
    {
        $secret = (string) config('modules.remote_provision_secret', '');

        if ($secret === '') {
            throw new \RuntimeException('MODULE_REMOTE_PROVISION_SECRET is not configured.');
        }

        if (str_starts_with($secret, 'base64:')) {
            $decoded = base64_decode(substr($secret, 7), true);

            return $decoded !== false ? $decoded : $secret;
        }

        return $secret;
    }
}
