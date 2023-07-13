<?php

declare(strict_types=1);

namespace Zaphyr\ContainerTests\TestAssets;

class UnresolvableDependency
{
    public function __construct(protected $value)
    {
    }
}
