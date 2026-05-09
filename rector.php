<?php

declare(strict_types=1);

use Odinns\CodingStyle\OdinnsRectorConfig;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    OdinnsRectorConfig::setup($rectorConfig);

    $rectorConfig->paths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);
};
