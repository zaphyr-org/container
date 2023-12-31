<?php

declare(strict_types=1);

namespace Zaphyr\Container\Contracts;

use Closure;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Zaphyr\Container\Exceptions\ContainerException;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
interface ContainerInterface extends PsrContainerInterface
{
    /**
     * @param string              $alias
     * @param Closure|string|null $concrete
     * @param bool                $singleton
     *
     * @return $this
     */
    public function bind(string $alias, Closure|string|null $concrete = null, bool $singleton = false): static;

    /**
     * @param string              $alias
     * @param Closure|string|null $concrete
     *
     * @return $this
     */
    public function bindSingleton(string $alias, Closure|string|null $concrete = null): static;

    /**
     * @param string $alias
     * @param object $instance
     *
     * @return $this
     */
    public function bindInstance(string $alias, object $instance): static;

    /**
     * @param string $alias
     *
     * @return bool
     */
    public function isSingleton(string $alias): bool;

    /**
     * @param array<mixed>|string|Closure $callable
     * @param array<mixed>                $parameters
     *
     * @throws ContainerException
     * @return mixed
     */
    public function call(array|string|Closure $callable, array $parameters = []): mixed;

    /**
     * @param string[]|string $aliases
     * @param string[]        $tags
     *
     * @return $this
     */
    public function tag(array|string $aliases, array $tags): static;

    /**
     * @param string $tag
     *
     * @throws ContainerException
     * @return iterable<mixed>
     */
    public function tagged(string $tag): iterable;

    /**
     * @param string  $alias
     * @param Closure $closure
     *
     * @return $this
     */
    public function extend(string $alias, Closure $closure): static;

    /**
     * @param ServiceProviderInterface $provider
     *
     * @throws ContainerException
     * @return $this
     */
    public function registerServiceProvider(ServiceProviderInterface $provider): static;
}
