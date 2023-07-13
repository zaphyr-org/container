<?php

declare(strict_types=1);

namespace Zaphyr\ContainerTests\TestAssets;

class ProtectedConstructor
{
    protected function __construct(public string $value = 'foo')
    {
    }
}
