<?php

declare(strict_types=1);

use Volven\Pest\Debt;

if (! function_exists('debt')) {
    function debt(?string $root = null): Debt
    {
        $workingDirectory = getcwd();

        return new Debt($root ?? ($workingDirectory === false ? '.' : $workingDirectory));
    }
}
