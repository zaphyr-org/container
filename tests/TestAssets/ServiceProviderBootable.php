<?php

declare(strict_types=1);

namespace Zaphyr\ContainerTests\TestAssets;

use Zaphyr\Container\AbstractServiceProvider;
use Zaphyr\Container\Contracts\BootableServiceProviderInterface;

class ServiceProviderBootable extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    public int $booted = 0;

    public int $registered = 0;

    protected array $provides = [
        'ServiceOne',
        'ServiceTwo'
    ];

    public function boot(): void
    {
        $this->booted++;
    }

    public function register(): void
    {
        $this->registered++;

        $this->getContainer()->bind('service', function ($arg) {
            return $arg;
        });
    }
}
