<?php

declare(strict_types=1);

namespace Zaphyr\Container\Contracts;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
interface ServiceProviderInterface extends ContainerAwareInterface
{
    /**
     * @return string
     */
    public function name(): string;

    /**
     * @param string $alias
     *
     * @return bool
     */
    public function provides(string $alias): bool;

    /**
     * @return void
     */
    public function register(): void;
}
