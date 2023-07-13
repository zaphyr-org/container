<?php

declare(strict_types=1);

namespace Zaphyr\ContainerTests\TestAssets;

class DefaultValues
{
    public function __construct(public Foo $foo, public string $value = 'bar')
    {
    }
}
