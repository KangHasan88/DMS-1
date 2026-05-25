<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuditClosureTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_and_debug_files_are_not_left_in_application_tree(): void
    {
        $paths = [
            base_path('.env.backup'),
            base_path('app.backup'),
            base_path('config.backup'),
            base_path('database.backup'),
            base_path('resources.backup'),
            base_path('routes.backup'),
            base_path('test_native.php'),
            public_path('test_hash.php'),
        ];

        foreach ($paths as $path) {
            $this->assertFileDoesNotExist($path);
        }
    }

    public function test_public_debug_file_is_not_reachable(): void
    {
        $this->get('/test_hash.php')->assertNotFound();
    }

    public function test_legacy_user_controllers_are_removed(): void
    {
        $this->assertFileDoesNotExist(app_path('Http/Controllers/UserController-before.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/UserController-old.php'));
    }

    public function test_no_routes_point_to_legacy_user_controllers(): void
    {
        $legacyActions = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();

            if (str_contains($action, 'UserController-before') || str_contains($action, 'UserController-old')) {
                $legacyActions[] = $route->uri() . ' => ' . $action;
            }
        }

        $this->assertSame([], $legacyActions);
    }
}
