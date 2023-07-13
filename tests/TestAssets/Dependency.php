<?php

declare(strict_types=1);

namespace Zaphyr\ContainerTests\TestAssets;

class Dependency
{
    public function __construct(public BarInterface $bar)
    {
    }
}
