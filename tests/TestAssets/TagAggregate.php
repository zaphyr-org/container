<?php

declare(strict_types=1);

namespace Zaphyr\ContainerTests\TestAssets;

class TagAggregate
{
    public array $tags;

    public function __construct(TagInterface ...$tags)
    {
        $this->tags = $tags;
    }
}
