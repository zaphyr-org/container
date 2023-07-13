<?php

declare(strict_types=1);

namespace Zaphyr\Container\Exceptions;

use Exception;
use Psr\Container\ContainerExceptionInterface;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class ContainerException extends Exception implements ContainerExceptionInterface
{
}
