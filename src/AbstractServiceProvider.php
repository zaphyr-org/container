<?php

declare(strict_types=1);

namespace Zaphyr\Container;

use Zaphyr\Container\Contracts\ServiceProviderInterface;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
abstract class AbstractServiceProvider implements ServiceProviderInterface
{
    use ContainerAwareTrait;

    /**
     * @var string[]
     */
    protected array $provides = [];

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return get_class($this);
    }

    /**
     * {@inheritdoc}
     */
    public function provides(string $alias): bool
    {
        return in_array($alias, $this->provides, true);
    }
}
