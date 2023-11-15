<?php

declare(strict_types=1);

namespace Zaphyr\ContainerTests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Zaphyr\Container\Container;
use Zaphyr\Container\ContainerAwareTrait;
use Zaphyr\Container\Contracts\ContainerAwareInterface;

class ContainerAwareTraitTest extends TestCase
{
    /**
     * @var Container
     */
    protected Container $container;

    public function setUp(): void
    {
        $this->container = new Container();
    }

    public function tearDown(): void
    {
        unset($this->container);
    }

    /* -------------------------------------------------
     * SET CONTAINER
     * -------------------------------------------------
     */

    public function testSetThrowsExceptionWhenInterfaceIsNotImplemented(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        $class = new class {
            use ContainerAwareTrait;
        };


        $class->setContainer($this->container);
    }

    /* -------------------------------------------------
     * GET CONTAINER
     * -------------------------------------------------
     */

    public function testGetThrowsExceptionWhenNoContainerInstanceIsAvailable(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        $class = new class implements ContainerAwareInterface {
            use ContainerAwareTrait;
        };


        $class->getContainer();
    }
}
