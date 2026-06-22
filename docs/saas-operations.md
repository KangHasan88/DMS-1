# DMS SaaS Operations

Dokumen ini menjelaskan kontrak SaaS DMS sebagai remote module untuk Kurmigo Central.

## Service Boundary

DMS berjalan di:

- Project: `/var/www/kurmigo-dms`
- Domain: `https://dms.kurmigo.id`

Jangan ubah Apache global, `/var/www/html`, `/var/www/kurmigo-usahaup`, atau project lain saat maintenance DMS. Smoke service IP sebelum dan sesudah deployment:

```bash
bash deploy/smoke-production.sh
```

Expected final status:

- Central login chain: `200`
- BMP report auth: `200`
- DMS login: `200`
- DMS health: `200`

## Environment Variables

Set these on DMS:

```env
MODULE_KEY=dms
MODULE_REMOTE_LAUNCH_SECRET=
MODULE_REMOTE_PROVISION_SECRET=
MODULE_REMOTE_LAUNCH_TTL=120
MODULE_REMOTE_PROVISION_TTL=300
MODULE_CENTRAL_PROVISIONING_CALLBACK_URL=
MODULE_CENTRAL_HEALTH_CALLBACK_URL=
```

`MODULE_REMOTE_LAUNCH_SECRET` and `MODULE_REMOTE_PROVISION_SECRET` must match Kurmigo Central.

## Remote Endpoints

Health:

```http
GET /health
```

Returns module status and `signed_health` payload for Central health callback.

Provisioning:

```http
POST /module-provisioning
```

Receives signed payload from Central and registers the tenant module locally in `saas_module_tenants`.

Launch:

```http
GET /sso/launch?...signed payload...
```

Requires:

- Valid launch signature.
- Tenant module exists in `saas_module_tenants`.
- Tenant module status is `provisioned`.

## Commands

Build signed health payload without sending:

```bash
php8.3 artisan saas:health-callback
```

Send health callback to Central:

```bash
php8.3 artisan saas:health-callback --send
```

Build provisioning callback payload without sending:

```bash
php8.3 artisan saas:provisioning-callback {tenant_module_id}
```

Send provisioning callback to Central:

```bash
php8.3 artisan saas:provisioning-callback {tenant_module_id} --send
```

## Test Safety

Do not run tests while production config is cached. The test bootstrap blocks this condition.

Safe test pattern:

```bash
php8.3 artisan config:clear
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=':memory:' CACHE_STORE=array SESSION_DRIVER=array QUEUE_CONNECTION=sync php8.3 artisan test tests/Feature/SaasRemoteModuleTest.php tests/Feature/SaasRemoteProvisioningTest.php
php8.3 artisan config:cache
php8.3 artisan event:cache
php8.3 artisan view:cache
```

## Deployment Checklist

1. Run `bash deploy/smoke-production.sh`.
2. Pull or deploy DMS code only.
3. Run DMS migrations.
4. Run targeted tests with testing env override.
5. Restore DMS production cache.
6. Run `bash deploy/smoke-production.sh` again.
