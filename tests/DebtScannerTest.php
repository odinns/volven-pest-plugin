<?php

declare(strict_types=1);

use Volven\Pest\Analysis\DebtScanner;
use Volven\Pest\Analysis\DebtFinding;
use Volven\Pest\Analysis\DebtType;

it('counts PHPStan suppressions without counting useful PHPStan type annotations', function (): void {
    $this->writeFile('src/Example.php', <<<'PHP'
<?php

/** @phpstan-type Payload array{id: int} */
/** @phpstan-import-type Payload from Other */
/** @phpstan-template T */
/** @phpstan-var Payload $payload */
/** @phpstan-param Payload $payload */
/** @phpstan-return Payload */
final class Example
{
    /** @phpstan-ignore-next-line temporary vendor bug tracked */
    public function run(mixed $value): mixed {}
}
PHP);

    $findings = (new DebtScanner($this->workspace))->scan();
    $phpstanIgnores = array_values(array_filter($findings, static fn (DebtFinding $finding): bool => $finding->type === DebtType::PhpstanIgnore));
    $mixedTypes = array_values(array_filter($findings, static fn (DebtFinding $finding): bool => $finding->type === DebtType::MixedType));

    expect($phpstanIgnores)->toHaveCount(1);
    expect($mixedTypes)->toHaveCount(2);
});

it('finds todos, fixmes, mixed types, and large classes', function (): void {
    $body = implode("\n", array_fill(0, 360, '    public function touch(): void {}'));

    $this->writeFile('src/LargeThing.php', <<<PHP
<?php

final class LargeThing
{
    // TODO wire this to tracked work
    // FIXME remove after import dies
    public function handle(mixed \$payload): mixed
    {
        {$body}
    }
}
PHP);

    $findings = (new DebtScanner($this->workspace))->scan();
    $types = array_map(static fn (DebtFinding $finding): string => $finding->type->value, $findings);

    expect($types)->toContain(DebtType::TodoComment->value);
    expect($types)->toContain(DebtType::MixedType->value);
    expect($types)->toContain(DebtType::LargeClass->value);
});
