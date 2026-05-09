# Vølven Pest Plugin

Vølven sees the code that will hurt later.

This Pest plugin adds deterministic technical-debt budgets to PHP projects. It scans for a small set of debt signals, fails when explicit budgets are exceeded, and can ratchet against a committed baseline so old debt stays visible without letting new debt sneak in.

It is meant for CI. Put the budget in a Pest test and let the build complain before the pile gets taller.

## What It Does

- Counts supported debt signals in PHP files.
- Fails a Pest test when a count exceeds its configured budget.
- Writes a baseline file for existing debt.
- Fails new growth with `mustNotGrow()`.
- Requires concrete reasons for PHPStan suppressions with `requireReasons()`.
- Reports failures in the Vølven finding shape: priority, future cost, evidence, smallest useful fix, and when to ignore it.

## What It Does Not Do

Vølven is not Pint, PHPStan, Psalm, Rector, Pest `arch()`, or a codemod.

Those tools already have jobs. Vølven tracks debt pressure: whether known debt is growing, whether suppressions are unexplained, and whether maintenance cost is moving in the wrong direction.

It does not fix code for you. It makes the debt visible enough that ignoring it becomes a decision.

## Supported Debt Signals

The current scanner looks at PHP files and ignores `vendor`, `node_modules`, and `.git`.

| Budget method | Signal |
| --- | --- |
| `phpstanIgnores(max: int)` | PHPStan suppression annotations |
| `todoComments(max: int)` | `TODO` and `FIXME` comments |
| `mixedTypes(max: int)` | `mixed` type usage |
| `largeClasses(max: int)` | Class-like PHP files at or above the large-file threshold |

PHPStan suppressions are counted. PHPStan type-shaping annotations are ignored.

Counted suppressions:

- `@phpstan-ignore`
- `@phpstan-ignore-line`
- `@phpstan-ignore-next-line`
- `@phpstan-ignore-*`

Ignored type-shaping annotations:

- `@phpstan-type`
- `@phpstan-import-type`
- `@phpstan-template`
- `@phpstan-var`
- `@phpstan-param`
- `@phpstan-return`

Contracts are not debt. Silencing uncertainty is.

## Install

```bash
composer require --dev odinns/volven-pest-plugin
```

## Basic Usage

Create a Pest test:

```php
<?php

it('keeps technical debt inside the budget', function (): void {
    debt()->budget()
        ->phpstanIgnores(max: 12)
        ->todoComments(max: 20)
        ->mixedTypes(max: 12)
        ->largeClasses(max: 5)
        ->assert();
});
```

Run Pest:

```bash
composer test
```

By default, `debt()` scans the current working directory. You can pass a root path when needed:

```php
debt(__DIR__.'/../src')
    ->budget()
    ->todoComments(max: 0)
    ->assert();
```

## Budget Examples

Strict new project:

```php
debt()->budget()
    ->phpstanIgnores(max: 0)
    ->todoComments(max: 0)
    ->mixedTypes(max: 0)
    ->largeClasses(max: 0)
    ->assert();
```

Legacy project with known debt:

```php
debt()->budget()
    ->phpstanIgnores(max: 25)
    ->todoComments(max: 40)
    ->mixedTypes(max: 30)
    ->largeClasses(max: 8)
    ->mustNotGrow()
    ->requireReasons()
    ->assert();
```

Focused budget for one signal:

```php
debt()->budget()
    ->phpstanIgnores(max: 10)
    ->requireReasons()
    ->assert();
```

## Baseline Generation

The baseline lives at `.volven/debt-baseline.json`.

Generate it from PHP:

```php
debt()->budget()
    ->phpstanIgnores(max: 999)
    ->todoComments(max: 999)
    ->mixedTypes(max: 999)
    ->largeClasses(max: 999)
    ->writeBaseline();
```

Commit the baseline file:

```bash
git add .volven/debt-baseline.json
git commit -m "chore: baseline volven debt"
```

The current MVP generates the baseline through PHP. There is no rich CLI yet. That can wait until the package has earned it.

## Ratchet Behavior

`mustNotGrow()` compares current findings against `.volven/debt-baseline.json`.

If the current count for a configured signal is higher than the baseline count, the test fails. The failure includes up to five new findings so the next fix is visible.

```php
debt()->budget()
    ->phpstanIgnores(max: 25)
    ->todoComments(max: 40)
    ->mustNotGrow()
    ->assert();
```

This lets a legacy project keep moving:

- Existing debt is captured.
- New debt fails CI.
- Reducing debt lowers the current count.
- Regenerating the baseline is an explicit review decision, not an accident.

## Reason Checks

`requireReasons()` applies to PHPStan suppressions.

A suppression needs at least three reason words after the annotation. These fail:

```php
/** @phpstan-ignore-next-line */
/** @phpstan-ignore-line legacy */
```

This passes:

```php
/** @phpstan-ignore-next-line vendor generic loses model type */
```

The point is not literary excellence. The point is to stop unexplained suppressions from becoming project sediment.

## Failure Output

Budget failures are thrown as `Volven\Pest\Analysis\BudgetFailure`.

Example:

```text
P1: PHPStan suppressions grew past the baseline
type: code
language: php
future cost: old debt can be captured, but new debt should fail CI before it becomes normal
evidence: baseline 12, current 14
smallest useful fix: remove the new findings or regenerate the baseline after review
ignore if: the increase is deliberate strategic debt with an owner and repayment path
- src/Example.php:14 /** @phpstan-ignore-next-line vendor generic loses model type */
```

Read it like a review finding:

- Priority tells you how serious the debt pressure is.
- Future cost explains why the signal matters.
- Evidence shows the count or concrete finding.
- Smallest useful fix gives the next move.
- Ignore-if names the valid escape hatch.

## CI Example

```yaml
name: tests

on:
  push:
  pull_request:

jobs:
  tests:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          tools: composer

      - run: composer install --no-interaction --prefer-dist
      - run: composer test
```

Use your project’s normal test command if it already wraps Pest.

## Development

```bash
composer install
composer test
composer test:types
composer test:refactor
composer test:all
```

Release checks for this package:

```bash
composer validate --strict
composer test:all
git diff --check
```

## Versioning

This package follows SemVer.

- Patch releases fix bugs or documentation.
- Minor releases add new debt signals or backwards-compatible API.
- Major releases may change budgets, baseline format, or failure semantics.

`composer.json` intentionally has no `version` field. Git tags are the source of package versions.

## Security

See [SECURITY.md](SECURITY.md).

Do not report vulnerabilities in public issues.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) and [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## License

MIT. See [LICENSE](LICENSE).
