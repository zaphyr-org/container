<?php

declare(strict_types=1);

namespace Zaphyr\ContainerTests\TestAssets;

class Call
{
    public function inject(Foo $foo, string $value = 'foo'): array
    {
        return func_get_args();
    }

    public static function injectStatic(Foo $foo, string $value = 'foo'): array
    {
        return func_get_args();
    }

    public function injectParams(): array
    {
        return func_get_args();
    }

    public function injectUnresolvable($foo, $bar)
    {
        return func_get_args();
    }
}
