<?php

declare(strict_types=1);

namespace Zaphyr\Container\Contracts;

use Zaphyr\Container\Exceptions\ContainerException;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
interface ContainerAwareInterface
{
    /**
     * @param ContainerInterface $container
     *
     * @throws ContainerException
     * @return $this
     */
    public function setContainer(ContainerInterface $container): static;

    /**
     * @throws ContainerException
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface;
}
