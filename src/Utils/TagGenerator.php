<?php

declare(strict_types=1);

namespace Zaphyr\Container\Utils;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @author   merloxx <merloxx@zaphyr.org>
 * @internal This class is not part of the public API and may change at any time!
 */
class TagGenerator implements Countable, IteratorAggregate
{
    /**
     * @var callable
     */
    protected $generator;

    /**
     * @var callable|int
     */
    protected $count;

    /**
     * @param callable     $generator
     * @param callable|int $count
     */
    public function __construct(callable $generator, callable|int $count)
    {
        $this->count = $count;
        $this->generator = $generator;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): Traversable
    {
        return ($this->generator)();
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        $count = $this->count;

        return is_callable($count) ? $count() : $count;
    }
}
