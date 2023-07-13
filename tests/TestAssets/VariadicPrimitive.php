<?php

declare(strict_types=1);

namespace Zaphyr\ContainerTests\TestAssets;

class VariadicPrimitive
{
    public array $args;

    public function __construct(...$args)
    {
        $this->args = $args;
    }
}
