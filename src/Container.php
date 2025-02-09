<?php

declare(strict_types=1);

namespace Zaphyr\Container;

use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Zaphyr\Container\Contracts\AggregateServiceProviderInterface;
use Zaphyr\Container\Contracts\ContainerInterface;
use Zaphyr\Container\Contracts\ServiceProviderInterface;
use Zaphyr\Container\Exceptions\ContainerException;
use Zaphyr\Container\Exceptions\NotFoundException;
use Zaphyr\Container\Utils\Reflector;
use Zaphyr\Container\Utils\TagGenerator;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class Container implements ContainerInterface
{
    /**
     * @var array<string, array<string, Closure|string|null|bool>>
     */
    protected array $bindings = [];

    /**
     * @var array<string, mixed>
     */
    protected array $singletons = [];

    /**
     * @var array<string, string[]>
     */
    protected array $tags = [];

    /**
     * @var array<string, Closure[]>
     */
    protected array $extends = [];

    /**
     * @var AggregateServiceProviderInterface
     */
    protected AggregateServiceProviderInterface $providers;

    /**
     * @param AggregateServiceProviderInterface|null $providers
     *
     * @throws ContainerExceptionInterface
     */
    public function __construct(?AggregateServiceProviderInterface $providers = null)
    {
        $this->providers = $providers ?? new AggregateServiceProvider();
        $this->providers->setContainer($this);
    }

    /**
     * {@inheritdoc}
     */
    public function bind(string $alias, Closure|string|null $concrete = null, bool $singleton = false): static
    {
        $concrete ??= $alias;

        $this->bindings[$alias] = compact('concrete', 'singleton');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function bindSingleton(string $alias, Closure|string|null $concrete = null): static
    {
        return $this->bind($alias, $concrete, true);
    }

    /**
     * {@inheritdoc}
     */
    public function bindInstance(string $alias, object $instance): static
    {
        $this->singletons[$alias] = $instance;

        return $this;
    }

    /**
     * @template T
     *
     * @param class-string<T>|string $id
     *
     * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
     * @throws ContainerExceptionInterface Error while retrieving the entry.
     * @return ($id is class-string<T> ? T : mixed)
     */
    public function get(string $id): mixed
    {
        try {
            return $this->resolve($id);
        } catch (ContainerExceptionInterface $exception) {
            if ($this->has($id)) {
                throw $exception;
            }

            throw new NotFoundException(
                '"' . $id . '" is not being managed by the container',
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * @param string $alias
     *
     * @throws ContainerExceptionInterface
     * @return mixed
     */
    protected function resolve(string $alias): mixed
    {
        if (isset($this->singletons[$alias])) {
            return $this->singletons[$alias];
        }

        if ($this->providers->provides($alias)) {
            $this->providers->register($alias);
        }

        $concrete = $this->getConcrete($alias);

        $object = $this->isBuildable($concrete, $alias) ? $this->build($concrete) : $this->resolve($concrete);

        if (count($this->extends) > 0) {
            foreach ($this->extends[$alias] as $extend) {
                $object = $extend($object, $this);
            }
        }

        if ($this->isSingleton($alias)) {
            $this->singletons[$alias] = $object;
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || $this->providers->provides($id);
    }

    /**
     * {@inheritdoc}
     */
    public function isSingleton(string $alias): bool
    {
        return isset($this->bindings[$alias]['singleton']) && $this->bindings[$alias]['singleton'] === true;
    }

    /**
     * @param string $alias
     *
     * @return mixed
     */
    protected function getConcrete(string $alias): mixed
    {
        if (isset($this->bindings[$alias])) {
            return $this->bindings[$alias]['concrete'];
        }

        return $alias;
    }

    /**
     * @param Closure|string $concrete
     * @param string         $alias
     *
     * @return bool
     */
    protected function isBuildable(Closure|string $concrete, string $alias): bool
    {
        return $concrete === $alias || $concrete instanceof Closure;
    }

    /**
     * @param Closure|string $concrete
     *
     * @throws ContainerExceptionInterface
     * @return mixed
     */
    protected function build(Closure|string $concrete): mixed
    {
        if ($concrete instanceof Closure) {
            return $concrete($this);
        }

        return Reflector::build($this, $concrete);
    }

    /**
     * {@inheritdoc}
     */
    public function call(array|string|Closure $callable, array $parameters = []): mixed
    {
        return Reflector::call($this, $callable, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function tag(array|string $aliases, array $tags): static
    {
        foreach ($tags as $tag) {
            if (!isset($this->tags[$tag])) {
                $this->tags[$tag] = [];
            }

            foreach ((array)$aliases as $alias) {
                $this->tags[$tag][] = $alias;
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function tagged(string $tag): iterable
    {
        if (!isset($this->tags[$tag])) {
            throw new ContainerException('Tag "' . $tag . '" is not defined');
        }

        return new TagGenerator(function () use ($tag) {
            foreach ($this->tags[$tag] as $abstract) {
                yield $this->resolve($abstract);
            }
        }, count($this->tags[$tag]));
    }

    /**
     * {@inheritdoc}
     */
    public function extend(string $alias, Closure $closure): static
    {
        if (isset($this->singletons[$alias])) {
            $this->singletons[$alias] = $closure($this->singletons[$alias], $this);
        } else {
            $this->extends[$alias][] = $closure;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function registerServiceProvider(ServiceProviderInterface $provider): static
    {
        $this->providers->add($provider);

        return $this;
    }
}
