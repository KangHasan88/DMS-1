<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReleaseGovernanceTest extends TestCase
{
    public function test_release_governance_document_defines_kanban_and_deploy_rules(): void
    {
        $document = file_get_contents(base_path('docs/release-governance.md'));

        $this->assertIsString($document);
        $this->assertStringContainsString('Backlog', $document);
        $this->assertStringContainsString('Ready', $document);
        $this->assertStringContainsString('In Progress', $document);
        $this->assertStringContainsString('Testing', $document);
        $this->assertStringContainsString('Review', $document);
        $this->assertStringContainsString('Done', $document);
        $this->assertStringContainsString('php8.3 artisan test', $document);
        $this->assertStringContainsString('bash deploy/smoke-production.sh', $document);
        $this->assertStringContainsString('commit hash', $document);
    }

    public function test_production_smoke_script_keeps_critical_services_guarded(): void
    {
        $script = file_get_contents(base_path('deploy/smoke-production.sh'));

        $this->assertIsString($script);
        $this->assertStringContainsString('central_http', $script);
        $this->assertStringContainsString('http://31.97.106.123/central', $script);
        $this->assertStringContainsString('central_https', $script);
        $this->assertStringContainsString('https://31.97.106.123/central', $script);
        $this->assertStringContainsString('bmp_auth', $script);
        $this->assertStringContainsString('https://31.97.106.123/dev/bmp/bmp_report/Auth', $script);
        $this->assertStringContainsString('dms_login', $script);
        $this->assertStringContainsString('https://dms.kurmigo.id/login', $script);
        $this->assertStringContainsString('dms_health', $script);
        $this->assertStringContainsString('https://dms.kurmigo.id/health', $script);
    }
}
