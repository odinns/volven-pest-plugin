<?php

declare(strict_types=1);

namespace Volven\Pest\Analysis;

final readonly class DebtFinding
{
    public function __construct(
        public DebtType $type,
        public string $path,
        public int $line,
        public string $evidence,
        public string $futureCost,
        public string $smallestUsefulFix,
        public string $ignoreIf,
        public ?string $reason = null,
    ) {
    }

    /**
     * @return array{type: string, path: string, line: int, evidence: string, fingerprint: string}
     */
    public function toBaselineEntry(): array
    {
        return [
            'type' => $this->type->value,
            'path' => $this->path,
            'line' => $this->line,
            'evidence' => $this->evidence,
            'fingerprint' => $this->fingerprint(),
        ];
    }

    public function fingerprint(): string
    {
        return sha1(implode('|', [
            $this->type->value,
            $this->path,
            trim($this->evidence),
        ]));
    }
}
