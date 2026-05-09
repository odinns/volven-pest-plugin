<?php

declare(strict_types=1);

namespace Volven\Pest\Analysis;

use JsonException;

final readonly class Baseline
{
    /**
     * @param array<string, array{count: int, fingerprints: list<string>}> $entries
     */
    private function __construct(private array $entries)
    {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public static function fromFile(string $path): self
    {
        if (! is_file($path)) {
            return self::empty();
        }

        try {
            $decoded = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new BudgetFailure("Vølven baseline is not valid JSON: {$path}", $exception->getCode(), previous: $exception);
        }

        if (! is_array($decoded)) {
            return self::empty();
        }

        $entries = [];
        $budgets = $decoded['budgets'] ?? [];

        if (! is_array($budgets)) {
            return self::empty();
        }

        foreach ($budgets as $type => $budget) {
            if (! is_string($type)) {
                continue;
            }

            if (! is_array($budget)) {
                continue;
            }

            $rawFingerprints = $budget['fingerprints'] ?? [];

            if (! is_array($rawFingerprints)) {
                $rawFingerprints = [];
            }

            $fingerprints = array_values(array_filter(
                $rawFingerprints,
                is_string(...),
            ));

            $entries[$type] = [
                'count' => is_int($budget['count'] ?? null) ? $budget['count'] : count($fingerprints),
                'fingerprints' => $fingerprints,
            ];
        }

        return new self($entries);
    }

    /**
     * @param list<DebtFinding> $findings
     *
     * @return array{version: int, budgets: array<string, array{count: int, fingerprints: list<string>, findings: list<array{type: string, path: string, line: int, evidence: string, fingerprint: string}>}>}
     */
    public static function createPayload(array $findings): array
    {
        $budgets = [];

        foreach ($findings as $finding) {
            $type = $finding->type->value;

            if (! isset($budgets[$type])) {
                $budgets[$type] = [
                    'count' => 0,
                    'fingerprints' => [],
                    'findings' => [],
                ];
            }

            $budgets[$type]['findings'][] = $finding->toBaselineEntry();
            $budgets[$type]['fingerprints'][] = $finding->fingerprint();
        }

        foreach ($budgets as $type => $budget) {
            $fingerprints = array_values(array_unique($budget['fingerprints']));
            $budgets[$type]['fingerprints'] = $fingerprints;
            $budgets[$type]['count'] = count($fingerprints);
        }

        ksort($budgets);

        return [
            'version' => 1,
            'budgets' => $budgets,
        ];
    }

    public function countFor(DebtType $type): int
    {
        return $this->entries[$type->value]['count'] ?? 0;
    }

    public function has(DebtFinding $finding): bool
    {
        return in_array($finding->fingerprint(), $this->entries[$finding->type->value]['fingerprints'] ?? [], true);
    }
}
