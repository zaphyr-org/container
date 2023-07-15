<?php

declare(strict_types=1);

namespace Zaphyr\Container\Utils;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Zaphyr\Container\Container;
use Zaphyr\Container\Exceptions\ContainerException;

/**
 * @author   merloxx <merloxx@zaphyr.org>
 * @internal This class is not part of the public API and may change at any time!
 */
class Reflector
{
    /**
     * @param Container $container
     * @param string    $concrete
     *
     * @throws ContainerException
     * @return mixed
     */
    public static function build(Container $container, string $concrete): mixed
    {
        if (!class_exists($concrete)) {
            throw new ContainerException('Could not resolve "' . $concrete . '"');
        }

        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            throw new ContainerException('"' . $concrete . '" is not instantiable');
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        $dependencies = $constructor->getParameters();
        $instances = static::resolveConstructorDependencies($container, $dependencies);

        try {
            return $reflector->newInstanceArgs($instances);
        } catch (ReflectionException $exception) {
            throw new ContainerException('Could not resolve "' . $concrete . '"', $exception->getCode(), $exception);
        }
    }

    /**
     * @param Container             $container
     * @param ReflectionParameter[] $parameters
     *
     * @throws ContainerException
     * @return array<mixed>
     */
    protected static function resolveConstructorDependencies(Container $container, array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $result = static::getParameterClassName($parameter) === null
                ? static::resolvePrimitive($parameter)
                : static::resolveClass($container, $parameter);

            if ($parameter->isVariadic()) {
                $dependencies = array_merge($dependencies, $result);
            } else {
                $dependencies[] = $result;
            }
        }

        return $dependencies;
    }

    /**
     * @param ReflectionParameter $parameter
     *
     * @throws ContainerException
     * @return mixed
     */
    protected static function resolvePrimitive(ReflectionParameter $parameter): mixed
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

    /**
     * @param Container           $container
     * @param ReflectionParameter $parameter
     *
     * @throws ContainerException
     * @return mixed
     */
    protected static function resolveClass(Container $container, ReflectionParameter $parameter): mixed
    {
        $className = static::getParameterClassName($parameter);

        if ($className === null || $parameter->isVariadic()) {
            throw new ContainerException(
                'Could not resolve dependency "' . $parameter . '" in class ' .
                $parameter->getDeclaringClass()->getName()
            );
        }

        return $container->get($className);
    }

    /**
     * @param ReflectionParameter $parameter
     *
     * @return string|null
     */
    protected static function getParameterClassName(ReflectionParameter $parameter): string|null
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        return $type->getName();
    }

    /**
     * @param Container                   $container
     * @param array<mixed>|string|Closure $callable
     * @param array<mixed>                $parameters
     *
     * @throws ContainerException
     * @return mixed
     */
    public static function call(Container $container, array|string|Closure $callable, array $parameters = []): mixed
    {
        if (is_string($callable)) {
            if (str_contains($callable, '::')) {
                $callable = explode('::', $callable);
            } elseif (method_exists($callable, '__invoke')) {
                $callable = [$callable, '__invoke'];
            }
        }

        if (is_object($callable)) {
            $callable = [$callable, '__invoke'];
        }

        try {
            if (is_array($callable)) {
                if (is_string($callable[0])) {
                    $callable[0] = $container->get($callable[0]);
                }

                $reflection = new ReflectionMethod($callable[0], $callable[1]);
                $dependencies = static::resolveMethodDependencies($container, $reflection, $parameters);

                return $reflection->invokeArgs($callable[0], $dependencies);
            }

            $reflection = new ReflectionFunction($callable);
            $dependencies = static::resolveMethodDependencies($container, $reflection, $parameters);

            return $reflection->invokeArgs($dependencies);
        } catch (ReflectionException $exception) {
            throw new ContainerException('Unable to resolve callable', $exception->getCode(), $exception);
        }
    }

    /**
     * @param Container                  $container
     * @param ReflectionFunctionAbstract $method
     * @param array<mixed>               $parameters
     *
     * @throws ContainerException
     * @return array<mixed>
     */
    protected static function resolveMethodDependencies(
        Container $container,
        ReflectionFunctionAbstract $method,
        array $parameters = []
    ): array {
        $dependencies = [];

        foreach ($method->getParameters() as $parameter) {
            $paramName = $parameter->getName();
            $className = static::getParameterClassName($parameter);

            if (array_key_exists($paramName, $parameters)) {
                $dependencies[] = $parameters[$paramName];

                unset($parameters[$paramName]);
            } elseif ($className !== null) {
                if (array_key_exists($className, $parameters)) {
                    $dependencies[] = $parameters[$className];

                    unset($parameters[$className]);
                } elseif ($parameter->isVariadic()) {
                    $variadicDependencies = $container->get($className);

                    $dependencies = array_merge(
                        $dependencies,
                        is_array($variadicDependencies) ? $variadicDependencies : [$variadicDependencies]
                    );
                } else {
                    $dependencies[] = $container->get($className);
                }
            } elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } elseif (!$parameter->isOptional()) {
                throw new ContainerException(
                    'Unable to resolve dependency "' . $parameter . '" in class "' .
                    $parameter->getDeclaringClass()->getName() . '"'
                );
            }
        }

        return array_merge($dependencies, array_values($parameters));
    }
}
