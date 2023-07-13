<?php

declare(strict_types=1);

namespace Zaphyr\ContainerTests\TestAssets;

class Foo
{
    public function __construct(public Bar $bar)
    {
    }
}
