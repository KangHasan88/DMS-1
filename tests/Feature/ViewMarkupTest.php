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
}
