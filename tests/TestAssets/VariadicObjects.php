<?php

declare(strict_types=1);

namespace Zaphyr\ContainerTests\TestAssets;

class VariadicObjects
{
    protected array $bars;

    public function __construct(BarInterface ...$bars)
    {
        $this->bars = $bars;
    }
}
