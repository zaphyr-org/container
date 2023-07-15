<?php

declare(strict_types=1);

namespace Zaphyr\ContainerTests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Zaphyr\Container\AggregateServiceProvider;
use Zaphyr\Container\Contracts\ContainerInterface;
use Zaphyr\ContainerTests\TestAssets\ServiceProviderBootable;

class AggregateServiceProviderTest extends TestCase
{
    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $containerMock;

    /**
     * @var AggregateServiceProvider
     */
    protected AggregateServiceProvider $aggregateServiceProvider;

    public function setUp(): void
    {
        $this->containerMock = $this->createMock(ContainerInterface::class);
        $this->aggregateServiceProvider = new AggregateServiceProvider();
        $this->aggregateServiceProvider->setContainer($this->containerMock);
    }

    public function tearDown(): void
    {
        unset($this->containerMock, $this->aggregateServiceProvider);
    }

    /* -------------------------------------------------
     * ADD | PROVIDES
     * -------------------------------------------------
     */

    public function testAddProvides(): void
    {
        $this->aggregateServiceProvider->add(new ServiceProviderBootable());

        self::assertTrue($this->aggregateServiceProvider->provides('ServiceOne'));
        self::assertTrue($this->aggregateServiceProvider->provides('ServiceTwo'));
    }

    public function testAddBootsOnlyOnce(): void
    {
        $serviceProvider = new ServiceProviderBootable();

        $this->aggregateServiceProvider->add($serviceProvider);
        $this->aggregateServiceProvider->add($serviceProvider);

        self::assertSame(1, $serviceProvider->booted);
    }
    /* -------------------------------------------------
     * REGISTER
     * -------------------------------------------------
     */

    public function testRegisterThrowsExceptionWhenServiceProviderInstanceNotAvailable(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        $this->aggregateServiceProvider->register('ServiceTwo');
    }

    public function testRegisterRegistersOnlyOnce(): void
    {
        $serviceProvider = new ServiceProviderBootable();

        $this->aggregateServiceProvider->add($serviceProvider);
        $this->aggregateServiceProvider->register('ServiceOne');
        $this->aggregateServiceProvider->register('ServiceOne');

        self::assertSame(1, $serviceProvider->registered);
    }
}
