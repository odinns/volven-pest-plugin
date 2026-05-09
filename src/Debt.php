<?php

declare(strict_types=1);

namespace Volven\Pest;

use Volven\Pest\Analysis\DebtBudget;

final readonly class Debt
{
    public function __construct(private string $root)
    {
    }

    public function budget(): DebtBudget
    {
        return new DebtBudget($this->root);
    }
}
