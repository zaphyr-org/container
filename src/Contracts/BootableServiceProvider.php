<?php

declare(strict_types=1);

namespace Zaphyr\Container\Contracts;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
interface BootableServiceProvider extends ServiceProviderInterface
{
    /**
     * @return void
     */
    public function boot(): void;
}
