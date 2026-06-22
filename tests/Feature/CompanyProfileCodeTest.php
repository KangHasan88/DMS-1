<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyProfileCodeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_default_company_profile_normalizes_document_code_to_three_chars(): void
    {
        $company = CompanyProfile::defaultProfile();

        $this->assertSame(3, strlen($company->code));
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{3}$/', $company->code);
    }

    public function test_document_code_part_helper_trims_branch_code_to_three_chars(): void
    {
        $this->assertSame('MAI', CompanyProfile::normalizeCodePart('MAIN', 'TNG'));
        $this->assertSame('TAN', CompanyProfile::normalizeCodePart('Tangerang', 'TNG'));
    }
}
