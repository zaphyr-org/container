<?php

declare(strict_types=1);

namespace Zaphyr\ContainerTests\TestAssets;

class ExtendLazy
{
    public static $init = false;

    public function init(): void
    {
        static::$init = true;
    }
}
