<?php

declare(strict_types=1);

namespace Zaphyr\Container\Exceptions;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class NotFoundException extends Exception implements NotFoundExceptionInterface
{
}
