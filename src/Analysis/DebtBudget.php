<?php

declare(strict_types=1);

namespace Volven\Pest\Analysis;

final class DebtBudget
{
    /** @var list<BudgetRule> */
    private array $rules = [];

    private bool $mustNotGrow = false;

    private bool $requireReasons = false;

    public function __construct(private readonly string $root)
    {
    }

    public function phpstanIgnores(int $max): self
    {
        return $this->addRule(DebtType::PhpstanIgnore, $max);
    }

    public function todoComments(int $max): self
    {
        return $this->addRule(DebtType::TodoComment, $max);
    }

    public function mixedTypes(int $max): self
    {
        return $this->addRule(DebtType::MixedType, $max);
    }

    public function largeClasses(int $max): self
    {
        return $this->addRule(DebtType::LargeClass, $max);
    }

    public function mustNotGrow(): self
    {
        $this->mustNotGrow = true;

        return $this;
    }

    public function requireReasons(): self
    {
        $this->requireReasons = true;

        return $this;
    }

    /**
     * @return list<DebtFinding>
     */
    public function findings(): array
    {
        return (new DebtScanner($this->root))->scan();
    }

    public function assert(): void
    {
        $findings = $this->findings();
        $baseline = Baseline::fromFile($this->root.'/.volven/debt-baseline.json');
        $failures = [];

        foreach ($this->rules as $rule) {
            $typedFindings = $this->filterByType($findings, $rule->type);
            $count = count($typedFindings);

            if ($count > $rule->max) {
                $failures[] = "P2: {$rule->type->label()} budget exceeded\nfuture cost: debt pressure can rise without an explicit decision\nevidence: {$count} found, max {$rule->max}\nsmallest useful fix: remove findings or raise the budget in the test with a reason\nignore if: this is a deliberate debt increase reviewed in the same change";
            }

            if ($this->mustNotGrow && $count > $baseline->countFor($rule->type)) {
                $newFindings = array_values(array_filter(
                    $typedFindings,
                    static fn (DebtFinding $finding): bool => ! $baseline->has($finding),
                ));

                $failures[] = $this->formatGrowthFailure($rule->type, $baseline->countFor($rule->type), $count, $newFindings);
            }
        }

        if ($this->requireReasons) {
            $missingReasons = array_values(array_filter(
                $this->filterByType($findings, DebtType::PhpstanIgnore),
                static fn (DebtFinding $finding): bool => self::reasonWordCount($finding->reason ?? '') < 3,
            ));

            if ($missingReasons !== []) {
                $failures[] = $this->formatReasonFailure($missingReasons);
            }
        }

        if ($failures !== []) {
            throw new BudgetFailure(implode("\n\n", $failures));
        }
    }

    public function writeBaseline(?string $path = null): void
    {
        $target = $path ?? $this->root.'/.volven/debt-baseline.json';
        $directory = dirname($target);

        if (! is_dir($directory)) {
            mkdir($directory, recursive: true);
        }

        file_put_contents($target, json_encode(Baseline::createPayload($this->findings()), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }

    private function addRule(DebtType $type, int $max): self
    {
        $this->rules[] = new BudgetRule($type, $max);

        return $this;
    }

    /**
     * @param list<DebtFinding> $findings
     *
     * @return list<DebtFinding>
     */
    private function filterByType(array $findings, DebtType $type): array
    {
        return array_values(array_filter(
            $findings,
            static fn (DebtFinding $finding): bool => $finding->type === $type,
        ));
    }

    /**
     * @param list<DebtFinding> $newFindings
     */
    private function formatGrowthFailure(DebtType $type, int $baselineCount, int $count, array $newFindings): string
    {
        $examples = array_slice($newFindings, 0, 5);
        $lines = [
            "P1: {$type->label()} grew past the baseline",
            'type: code',
            'language: php',
            "future cost: old debt can be captured, but new debt should fail CI before it becomes normal",
            "evidence: baseline {$baselineCount}, current {$count}",
            'smallest useful fix: remove the new findings or regenerate the baseline after review',
            'ignore if: the increase is deliberate strategic debt with an owner and repayment path',
        ];

        foreach ($examples as $finding) {
            $lines[] = "- {$finding->path}:{$finding->line} {$finding->evidence}";
        }

        return implode("\n", $lines);
    }

    /**
     * @param list<DebtFinding> $missingReasons
     */
    private function formatReasonFailure(array $missingReasons): string
    {
        $lines = [
            'P2: PHPStan suppressions need reasons',
            'type: type',
            'language: php',
            'future cost: unexplained suppressions hide whether the debt is temporary, stale, or cargo cult',
            'evidence: suppressions with fewer than 3 reason words',
            'smallest useful fix: add a concrete reason after the suppression',
            'ignore if: the suppression is generated code outside the budgeted paths',
        ];

        foreach (array_slice($missingReasons, 0, 5) as $finding) {
            $lines[] = "- {$finding->path}:{$finding->line} {$finding->evidence}";
        }

        return implode("\n", $lines);
    }

    private static function reasonWordCount(string $reason): int
    {
        preg_match_all('/[a-z0-9]+/i', $reason, $words);

        return count($words[0]);
    }
}
