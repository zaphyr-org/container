<?php

declare(strict_types=1);

namespace Zaphyr\Container;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class TagGenerator implements Countable, IteratorAggregate
{
    protected $generator;

    protected $count;

    public function __construct(callable $generator, callable|int $count)
    {
        $this->count = $count;
        $this->generator = $generator;
    }

    public function getIterator(): Traversable
    {
        return ($this->generator)();
    }

    public function count(): int
    {
        if (is_callable($count = $this->count)) {
            $this->count = $count();
        }

        return $this->count;
    }
}
