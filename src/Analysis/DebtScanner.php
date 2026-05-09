<?php

declare(strict_types=1);

namespace Volven\Pest\Analysis;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final readonly class DebtScanner
{
    /**
     * @param list<string> $excludedDirectories
     */
    public function __construct(
        private string $root,
        private array $excludedDirectories = ['vendor', 'node_modules', '.git'],
        private int $largeClassLineThreshold = 350,
    ) {
    }

    /**
     * @return list<DebtFinding>
     */
    public function scan(): array
    {
        $findings = [];

        foreach ($this->phpFiles() as $file) {
            $path = $file->getPathname();
            $relativePath = $this->relativePath($path);
            $lines = file($path, FILE_IGNORE_NEW_LINES);

            if ($lines === false) {
                continue;
            }

            array_push(
                $findings,
                ...$this->scanLines($relativePath, $lines),
                ...$this->scanLargeClasses($relativePath, $lines),
            );
        }

        return $findings;
    }

    /**
     * @return list<SplFileInfo>
     */
    private function phpFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root));

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo) {
                continue;
            }

            if (! $file->isFile()) {
                continue;
            }

            if ($file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();

            if ($this->isExcluded($path)) {
                continue;
            }

            $files[] = $file;
        }

        usort($files, static fn (SplFileInfo $a, SplFileInfo $b): int => $a->getPathname() <=> $b->getPathname());

        return $files;
    }

    private function isExcluded(string $path): bool
    {
        foreach ($this->excludedDirectories as $directory) {
            if (str_contains($path, DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $lines
     *
     * @return list<DebtFinding>
     */
    private function scanLines(string $path, array $lines): array
    {
        $findings = [];

        foreach ($lines as $index => $line) {
            $lineNumber = $index + 1;

            if (preg_match('/@phpstan-ignore(?:-[a-z-]+)?\b(?<reason>.*)$/i', $line, $match) === 1) {
                $findings[] = new DebtFinding(
                    DebtType::PhpstanIgnore,
                    $path,
                    $lineNumber,
                    trim($line),
                    'suppressed static-analysis uncertainty can survive upgrades and hide real type-contract drift',
                    'remove the suppression or add a concrete reason and repayment path',
                    'the suppression is temporary, explained, and tracked',
                    trim($match['reason']),
                );
            }

            if (preg_match('/\b(?:TODO|FIXME)\b(?<reason>.*)$/i', $line, $match) === 1) {
                $findings[] = new DebtFinding(
                    DebtType::TodoComment,
                    $path,
                    $lineNumber,
                    trim($line),
                    'unowned work can become stale local folklore',
                    'turn the comment into tracked work or make the next concrete fix',
                    'the comment is generated, external, or already linked to active work',
                    trim($match['reason']),
                );
            }

            if (preg_match_all('/(?<![a-zA-Z0-9_\\\\])mixed(?!\w)/', $line, $matches) > 0) {
                foreach ($matches[0] as $matchIndex => $match) {
                    $findings[] = new DebtFinding(
                        DebtType::MixedType,
                        $path,
                        $lineNumber,
                        trim($line).' #'.($matchIndex + 1).' '.$match,
                        'important data can cross boundaries without a visible contract',
                        'replace mixed with a shaped array, value object, generic, or documented boundary type',
                        'this is an adapter edge where the unknown shape is immediately validated',
                    );
                }
            }
        }

        return $findings;
    }

    /**
     * @param list<string> $lines
     *
     * @return list<DebtFinding>
     */
    private function scanLargeClasses(string $path, array $lines): array
    {
        $lineCount = count($lines);

        if ($lineCount < $this->largeClassLineThreshold || ! $this->containsClass($lines)) {
            return [];
        }

        return [
            new DebtFinding(
                DebtType::LargeClass,
                $path,
                1,
                "{$lineCount} lines in one class-like file",
                'large class-like files tend to trap unrelated reasons to change',
                'extract the next changed responsibility, not the whole file',
                'the file is generated, a fixture, or a stable protocol table',
            ),
        ];
    }

    /**
     * @param list<string> $lines
     */
    private function containsClass(array $lines): bool
    {
        foreach ($lines as $line) {
            if (preg_match('/^\s*(?:final\s+|abstract\s+)?(?:readonly\s+)?(?:class|trait|interface|enum)\s+\w+/', $line) === 1) {
                return true;
            }
        }

        return false;
    }

    private function relativePath(string $path): string
    {
        $root = rtrim($this->root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return str_starts_with($path, $root) ? substr($path, strlen($root)) : $path;
    }
}
