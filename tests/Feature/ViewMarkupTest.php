<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ViewMarkupTest extends TestCase
{
    public function test_blade_views_do_not_contain_broken_placeholder_markers(): void
    {
        $brokenMarkers = collect(File::allFiles(resource_path('views')))
            ->flatMap(function ($file) {
                return collect(file($file->getPathname()))
                    ->map(fn (string $line, int $index) => [
                        'path' => $file->getRelativePathname(),
                        'line' => $index + 1,
                        'content' => trim($line),
                    ]);
            })
            ->filter(function (array $line) {
                return $line['content'] === '??'
                    || str_contains($line['content'], '??<')
                    || str_contains($line['content'], '>??')
                    || str_contains($line['content'], '}}??')
                    || str_contains($line['content'], '??{{');
            })
            ->map(fn (array $line) => "{$line['path']}:{$line['line']} {$line['content']}")
            ->values();

        $this->assertSame([], $brokenMarkers->all());
    }

    public function test_supplier_module_uses_indonesian_pemasok_copy(): void
    {
        $files = [
            resource_path('views/suppliers/index.blade.php'),
            resource_path('views/suppliers/show.blade.php'),
            resource_path('views/suppliers/create.blade.php'),
            resource_path('views/suppliers/edit.blade.php'),
        ];

        foreach ($files as $file) {
            $content = file_get_contents($file);

            $this->assertStringNotContainsString('Supplier Management', $content);
            $this->assertStringNotContainsString('Detail Supplier', $content);
            $this->assertStringNotContainsString('Tambah Supplier', $content);
            $this->assertStringNotContainsString('Edit Supplier', $content);
            $this->assertStringNotContainsString('Nama Supplier', $content);
        }
    }
}
