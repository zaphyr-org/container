<?php

declare(strict_types=1);

namespace Zaphyr\Container;

use Zaphyr\Container\Contracts\ContainerAwareInterface;
use Zaphyr\Container\Contracts\ContainerInterface;
use Zaphyr\Container\Exceptions\ContainerException;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
trait ContainerAwareTrait
{
    /**
     * @var ContainerInterface|null
     */
    protected ContainerInterface|null $container = null;

    /**
     * @param ContainerInterface $container
     *
     * @throws ContainerException
     * @return $this
     */
    public function setContainer(ContainerInterface $container): static
    {
        $this->container = $container;

        if ($this instanceof ContainerAwareInterface) {
            return $this;
        }

        throw new ContainerException(
            'Attempt to use "' . ContainerAwareTrait::class . '" without implementing "' .
            ContainerAwareInterface::class . '"'
        );
    }

    /**
     * @throws ContainerException
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        if ($this->container instanceof ContainerInterface) {
            return $this->container;
        }

        throw new ContainerException('No container implementation has been set');
    }
}
