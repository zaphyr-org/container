<?php

declare(strict_types=1);

namespace Zaphyr\Container;

use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Zaphyr\Container\Exceptions\ContainerException;

/**
 * @author   merloxx <merloxx@zaphyr.org>
 * @internal This class is not part of the public API and may change at any time!
 */
class DependencyResolver
{
    public static function getParameterClassName(ReflectionParameter $parameter): string|null
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();
        $class = $parameter->getDeclaringClass();

        if ($class !== null) {
            if ($name === 'self') {
                return $class->getName();
            }

            $parent = $class->getParentClass();

            if ($name === 'parent' && $parent) {
                return $parent->getName();
            }
        }

        return $name;
    }

    public static function call($container, $callable, $parameters)
    {
        if (is_string($callable)) {
            if (str_contains($callable, '::')) {
                $callable = explode('::', $callable);
            } elseif (method_exists($callable, '__invoke')) {
                $callable = [$callable, '__invoke'];
            }
        }

        if (is_array($callable)) {
            if (is_string($callable[0])) {
                $callable[0] = $container->resolve($callable[0]);
            }

            $reflection = new ReflectionMethod($callable[0], $callable[1]);
            $dependencies = static::resolveMethodDependencies($container, $reflection, $parameters);

            return $reflection->invokeArgs($callable[0], $dependencies);
        }

        if (is_object($callable)) {
            $reflection = new ReflectionMethod($callable, '__invoke');
            $dependencies = static::resolveMethodDependencies($container, $reflection, $parameters);

            return $reflection->invokeArgs($callable, $dependencies);
        }

        $reflection = new ReflectionFunction($callable);
        $dependencies = static::resolveMethodDependencies($container, $reflection, $parameters);

        return $reflection->invokeArgs($dependencies);
    }

    protected static function resolveMethodDependencies($container, ReflectionFunctionAbstract $method, $parameters)
    {
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
                    $variadicDependencies = $container->resolve($className);

                    $dependencies = array_merge(
                        $dependencies,
                        is_array($variadicDependencies) ? $variadicDependencies : [$variadicDependencies]
                    );
                } else {
                    $dependencies[] = $container->resolve($className);
                }
            } elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } elseif (!array_key_exists($paramName, $parameters) && !$parameter->isOptional()) {
                throw new ContainerException(
                    'Unable to resolve dependency "' . $parameter . '" in class "' .
                    $parameter->getDeclaringClass()->getName() . '"'
                );
            }
        }

        return array_merge($dependencies, array_values($parameters));
    }
}
