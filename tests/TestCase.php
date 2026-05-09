<?php

declare(strict_types=1);

namespace Volven\Pest\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public string $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workspace = sys_get_temp_dir().'/volven-pest-'.bin2hex(random_bytes(6));
        mkdir($this->workspace, recursive: true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->workspace);

        parent::tearDown();
    }

    public function writeFile(string $path, string $contents): void
    {
        $target = $this->workspace.'/'.$path;
        $directory = dirname($target);

        if (! is_dir($directory)) {
            mkdir($directory, recursive: true);
        }

        file_put_contents($target, $contents);
    }

    private function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $items = array_diff(scandir($path) ?: [], ['.', '..']);

        foreach ($items as $item) {
            $target = $path.'/'.$item;

            if (is_dir($target)) {
                $this->deleteDirectory($target);

                continue;
            }

            unlink($target);
        }

        rmdir($path);
    }
}
