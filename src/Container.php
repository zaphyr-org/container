<?php

declare(strict_types=1);

namespace Zaphyr\Container;

use Closure;
use Zaphyr\Container\Contracts\ContainerInterface;
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
    protected array $instances = [];

    /**
     * @var array<string, string[]>
     */
    protected array $tags = [];

    /**
     * @var array<string, Closure[]>
     */
    protected array $extends = [];

    /**
     * {@inheritdoc}
     */
    public function bind(string $alias, Closure|string|null $concrete = null, bool $shared = false): void
    {
        if ($concrete === null) {
            $concrete = $alias;
        }

        $this->bindings[$alias] = compact('concrete', 'shared');
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $alias): mixed
    {
        if (isset($this->instances[$alias])) {
            return $this->instances[$alias];
        }

        $concrete = $this->getConcrete($alias);

        $object = $this->isBuildable($concrete, $alias) ? $this->build($concrete) : $this->resolve($concrete);

        if (count($this->extends) > 0) {
            foreach ($this->extends[$alias] as $extend) {
                $object = $extend($object, $this);
            }
        }

        if ($this->isShared($alias)) {
            $this->instances[$alias] = $object;
        }

        return $object;
    }

    /**
     * @template T
     *
     * @param class-string<T>|string $id
     *
     * {@inheritdoc}
     */
    public function get(string $id): mixed
    {
        try {
            return $this->resolve($id);
        } catch (ContainerException $exception) {
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
     * {@inheritdoc}
     */
    public function has(string $id): bool
    {
        return isset($this->bindings[$id]);
    }

    /**
     * {@inheritdoc}
     */
    public function isShared(string $alias): bool
    {
        return isset($this->bindings[$alias]['shared']) && $this->bindings[$alias]['shared'] === true;
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
     * @throws ContainerException
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
    public function tag(array|string $aliases, array $tags): void
    {
        foreach ($tags as $tag) {
            if (!isset($this->tags[$tag])) {
                $this->tags[$tag] = [];
            }

            foreach ((array)$aliases as $alias) {
                $this->tags[$tag][] = $alias;
            }
        }
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
    public function extend(string $alias, Closure $closure): void
    {
        if (isset($this->instances[$alias])) {
            $this->instances[$alias] = $closure($this->instances[$alias], $this);
        } else {
            $this->extends[$alias][] = $closure;
        }
    }
}
