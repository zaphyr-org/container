<?php

declare(strict_types=1);

namespace Zaphyr\ContainerTests\TestAssets;

use Zaphyr\Container\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{
    protected array $provides = [
        Foo::class,
        Bar::class,
        'value',
        'liar',
    ];

    public function register(): void
    {
        $this->container
            ->bind(Foo::class)
            ->bind(Bar::class)
            ->bind('value', fn() => 'foo');
    }
}
