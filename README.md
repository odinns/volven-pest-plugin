# Vølven Pest Plugin

Vølven sees the code that will hurt later.

This Pest plugin adds deterministic technical-debt budgets to PHP projects. It is not a formatter, type checker, codemod, or architecture-test clone. Those jobs are already taken.

Vølven tracks debt pressure:

- PHPStan inline suppressions
- TODO and FIXME comments
- `mixed` types
- large class-like files

Old debt can be captured in a committed baseline. New debt fails CI.

## Install

```bash
composer require --dev odinns/volven-pest-plugin
```

## Use

```php
debt()->budget()
    ->phpstanIgnores(max: 12)
    ->todoComments(max: 20)
    ->mixedTypes(max: 12)
    ->largeClasses(max: 5)
    ->mustNotGrow()
    ->requireReasons()
    ->assert();
```

## Baseline

The baseline lives at `.volven/debt-baseline.json`.

Generate it from a Pest test or setup script:

```php
debt()->budget()
    ->phpstanIgnores(max: 999)
    ->todoComments(max: 999)
    ->mixedTypes(max: 999)
    ->largeClasses(max: 999)
    ->writeBaseline();
```

Commit the baseline. After that, `mustNotGrow()` compares current findings against it.

This first slice keeps baseline generation small on purpose. A rich CLI can wait.

## Boundaries

Vølven does not count every `@phpstan-*` annotation. Type-shaping annotations such as `@phpstan-type`, `@phpstan-import-type`, `@phpstan-template`, `@phpstan-var`, `@phpstan-param`, and `@phpstan-return` are contracts, not suppressions.

It counts suppression annotations:

- `@phpstan-ignore`
- `@phpstan-ignore-line`
- `@phpstan-ignore-next-line`
- `@phpstan-ignore-*`
