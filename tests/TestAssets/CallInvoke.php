<?php

declare(strict_types=1);

namespace Zaphyr\ContainerTests\TestAssets;

class CallInvoke
{
    public function __construct(protected Foo $foo, protected string $value = 'foo')
    {
    }

    public function __invoke(Call $call): array
    {
        return [$this->foo, $this->value, $call];
    }
}
