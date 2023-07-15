<?php

declare(strict_types=1);

namespace Zaphyr\Container\Contracts;

use Zaphyr\Container\Exceptions\ContainerException;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
interface AggregateServiceProviderInterface extends ContainerAwareInterface
{
    /**
     * @param ServiceProviderInterface $provider
     *
     * @throws ContainerException
     * @return $this
     */
    public function add(ServiceProviderInterface $provider): static;

    /**
     * @param string $provider
     *
     * @throws ContainerException
     * @return bool
     */
    public function provides(string $provider): bool;

    /**
     * @param string $provider
     *
     * @throws ContainerException
     * @return void
     */
    public function register(string $provider): void;
}
