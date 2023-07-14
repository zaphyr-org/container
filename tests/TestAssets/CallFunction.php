<?php

declare(strict_types=1);

namespace Zaphyr\ContainerTests\TestAssets;

function callFunction(Foo $foo, string $value = 'foo'): array
{
    return func_get_args();
}
