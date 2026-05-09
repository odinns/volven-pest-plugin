<?php

declare(strict_types=1);

use Volven\Pest\Analysis\BudgetFailure;
use Volven\Pest\Analysis\DebtBudget;

it('passes when current findings stay within explicit budgets', function (): void {
    $this->writeFile('src/CleanEnough.php', <<<'PHP'
<?php

final class CleanEnough
{
    public function run(): void {}
}
PHP);

    (new DebtBudget($this->workspace))
        ->phpstanIgnores(max: 0)
        ->todoComments(max: 0)
        ->mixedTypes(max: 0)
        ->largeClasses(max: 0)
        ->assert();

    expect(true)->toBeTrue();
});

it('fails with readable debt-shaped output when a budget is exceeded', function (): void {
    $this->writeFile('src/Example.php', <<<'PHP'
<?php

final class Example
{
    /** @phpstan-ignore-next-line */
    public function run(): void {}
}
PHP);

    (new DebtBudget($this->workspace))
        ->phpstanIgnores(max: 0)
        ->assert();
})->throws(BudgetFailure::class, 'P2: PHPStan suppressions budget exceeded');

it('writes and reads a baseline, then fails when debt grows', function (): void {
    $this->writeFile('src/Example.php', <<<'PHP'
<?php

final class Example
{
    /** @phpstan-ignore-next-line temporary vendor bug tracked */
    public function run(): void {}
}
PHP);

    $budget = (new DebtBudget($this->workspace))->phpstanIgnores(max: 5);
    $budget->writeBaseline();

    $this->writeFile('src/NewDebt.php', <<<'PHP'
<?php

final class NewDebt
{
    /** @phpstan-ignore-line new silence */
    public function run(): void {}
}
PHP);

    (new DebtBudget($this->workspace))
        ->phpstanIgnores(max: 5)
        ->mustNotGrow()
        ->assert();
})->throws(BudgetFailure::class, 'P1: PHPStan suppressions grew past the baseline');

it('requires concrete reasons for PHPStan suppressions', function (): void {
    $this->writeFile('src/Example.php', <<<'PHP'
<?php

final class Example
{
    /** @phpstan-ignore-next-line */
    public function run(): void {}
}
PHP);

    (new DebtBudget($this->workspace))
        ->phpstanIgnores(max: 5)
        ->requireReasons()
        ->assert();
})->throws(BudgetFailure::class, 'P2: PHPStan suppressions need reasons');
