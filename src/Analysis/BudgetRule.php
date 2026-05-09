<?php

declare(strict_types=1);

namespace Volven\Pest\Analysis;

final readonly class BudgetRule
{
    public function __construct(
        public DebtType $type,
        public int $max,
    ) {
    }
}
