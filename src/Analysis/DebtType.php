<?php

declare(strict_types=1);

namespace Volven\Pest\Analysis;

enum DebtType: string
{
    case LargeClass = 'large_classes';
    case MixedType = 'mixed_types';
    case PhpstanIgnore = 'phpstan_ignores';
    case TodoComment = 'todo_comments';

    public function label(): string
    {
        return match ($this) {
            self::LargeClass => 'large classes',
            self::MixedType => 'mixed types',
            self::PhpstanIgnore => 'PHPStan suppressions',
            self::TodoComment => 'TODO/FIXME comments',
        };
    }
}
