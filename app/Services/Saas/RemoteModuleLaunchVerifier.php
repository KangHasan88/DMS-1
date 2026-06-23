<?php

namespace App\Services\Saas;

use Illuminate\Support\Arr;

class RemoteModuleLaunchVerifier
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

    public function context(array $payload): array
    {
        return $this->canonicalPayload($payload);
    }

    private function canonicalPayload(array $payload): array
    {
        return [
            'tenant_id' => (string) ($payload['tenant_id'] ?? ''),
            'tenant_module_id' => (string) ($payload['tenant_module_id'] ?? ''),
            'module_key' => (string) ($payload['module_key'] ?? ''),
            'tenant_user_id' => (string) ($payload['tenant_user_id'] ?? ''),
            'tenant_user_role' => (string) ($payload['tenant_user_role'] ?? ''),
            'expires' => (int) ($payload['expires'] ?? 0),
        ];
    }

    private function secret(): string
    {
        $secret = (string) config('modules.remote_launch_secret', '');

        if ($secret === '') {
            throw new \RuntimeException('MODULE_REMOTE_LAUNCH_SECRET is not configured.');
        }

        if (str_starts_with($secret, 'base64:')) {
            $decoded = base64_decode(substr($secret, 7), true);

            return $decoded !== false ? $decoded : $secret;
        }

        return $secret;
    }
}
