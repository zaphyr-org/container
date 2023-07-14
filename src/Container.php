<?php

declare(strict_types=1);

namespace Zaphyr\Container;

use Closure;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use Zaphyr\Container\Exceptions\ContainerException;
use Zaphyr\Container\Exceptions\NotFoundException;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class Container implements PsrContainerInterface
{

    protected array $bindings = [];
    protected array $instances = [];

    protected array $tags = [];

    protected array $extends = [];

    public function bind(string $alias, Closure|string|null $concrete = null, bool $shared = false): void
    {
        if ($concrete === null) {
            $concrete = $alias;
        }

        $this->bindings[$alias] = compact('concrete', 'shared');
    }

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

    public function get(string $id)
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

    public function has(string $id): bool
    {
        return isset($this->bindings[$id]);
    }

    public function isShared(string $alias): bool
    {
        return isset($this->bindings[$alias]['shared']) && $this->bindings[$alias]['shared'] === true;
    }

    protected function getConcrete(string $alias): mixed
    {
        if (isset($this->bindings[$alias])) {
            return $this->bindings[$alias]['concrete'];
        }

        return $alias;
    }

    protected function isBuildable(Closure|string $concrete, string $alias): bool
    {
        return $concrete === $alias || $concrete instanceof Closure;
    }

    protected function build(Closure|string $concrete): mixed
    {
        if ($concrete instanceof Closure) {
            return $concrete($this);
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $exception) {
            throw new ContainerException('Could not resolve "' . $concrete . '"', 0, $exception);
        }

        if (!$reflector->isInstantiable()) {
            throw new ContainerException('"' . $concrete . '" is not instantiable');
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        if (!$constructor->isPublic()) {
            throw new ContainerException('Constructor of class "' . $concrete . '" is not public');
        }

        $dependencies = $constructor->getParameters();
        $instances = $this->resolveDependencies($dependencies);

        return $reflector->newInstanceArgs($instances);
    }

    protected function resolveDependencies(array $dependencies): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            $result = DependencyResolver::getParameterClassName($dependency) === null
                ? $this->resolvePrimitive($dependency)
                : $this->resolveClass($dependency);

            if ($dependency->isVariadic()) {
                $results = array_merge($results, $result);
            } else {
                $results[] = $result;
            }
        }

        return $results;
    }

    protected function resolvePrimitive(ReflectionParameter $parameter): mixed
    {
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->isVariadic()) {
            return [];
        }

        throw new ContainerException(
            'Could not resolve dependency "' . $parameter . '" in class ' .
            $parameter->getDeclaringClass()->getName()
        );
    }

    protected function resolveClass(ReflectionParameter $parameter): mixed
    {
        if ($parameter->isVariadic()) {
            throw new ContainerException(
                'Could not resolve variadic dependency "' . $parameter . '" in class ' .
                $parameter->getDeclaringClass()->getName()
            );
        }

        return $this->resolve(DependencyResolver::getParameterClassName($parameter));
    }

    public function call($callable, array $parameters = []): mixed
    {
        return DependencyResolver::call($this, $callable, $parameters);
    }

    public function tag($aliases, array $tags): void
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

    public function tagged(string $tag): iterable
    {
        if (! isset($this->tags[$tag])) {
            throw new ContainerException('Tag "' . $tag . '" is not defined');
        }

        return new TagGenerator(function () use ($tag) {
            foreach ($this->tags[$tag] as $abstract) {
                yield $this->resolve($abstract);
            }
        }, count($this->tags[$tag]));
    }

    public function extend(string $alias, Closure $closure): void
    {
        if (isset($this->instances[$alias])) {
            $this->instances[$alias] = $closure($this->instances[$alias], $this);
        } else {
            $this->extends[$alias][] = $closure;
        }
    }
}
