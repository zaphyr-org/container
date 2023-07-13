<?php

declare(strict_types=1);

namespace Zaphyr\ContainerTests\TestAssets;

class NestedDependency
{
    public function __construct(public Dependency $dependency)
    {
    }
}
