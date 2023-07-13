<?php

declare(strict_types=1);

namespace Zaphyr\ContainerTests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use stdClass;
use Zaphyr\Container\Container;
use Zaphyr\ContainerTests\TestAssets\Bar;
use Zaphyr\ContainerTests\TestAssets\BarInterface;
use Zaphyr\ContainerTests\TestAssets\DefaultValues;
use Zaphyr\ContainerTests\TestAssets\Dependency;
use Zaphyr\ContainerTests\TestAssets\ExtendLazy;
use Zaphyr\ContainerTests\TestAssets\Foo;
use Zaphyr\ContainerTests\TestAssets\NestedDependency;
use Zaphyr\ContainerTests\TestAssets\ProtectedConstructor;
use Zaphyr\ContainerTests\TestAssets\TagAggregate;
use Zaphyr\ContainerTests\TestAssets\TagOne;
use Zaphyr\ContainerTests\TestAssets\TagTwo;
use Zaphyr\ContainerTests\TestAssets\UnresolvableDependency;
use Zaphyr\ContainerTests\TestAssets\VariadicObjects;
use Zaphyr\ContainerTests\TestAssets\VariadicPrimitive;

class ContainerTest extends TestCase
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
     * BIND
     * -------------------------------------------------
     */

    public function testBind(): void
    {
        $this->container->bind(Foo::class);
        $this->container->bind(Bar::class);

        self::assertInstanceOf(Foo::class, $foo = $this->container->resolve(Foo::class));
        self::assertInstanceOf(Bar::class, $foo->bar);
    }

    public function testBindWithStringConcrete(): void
    {
        $this->container->bind(BarInterface::class, Bar::class);

        self::assertInstanceOf(Bar::class, $this->container->resolve(BarInterface::class));
    }

    public function testBindWithClosureConcrete(): void
    {
        $this->container->bind('foo', fn() => 'Foo');

        self::assertEquals('Foo', $this->container->resolve('foo'));
    }

    public function testBindContainerIsPassedToClosure(): void
    {
        $this->container->bind('foo', fn(Container $container) => $container);

        self::assertInstanceOf(Container::class, $this->container->resolve('foo'));
    }

    public function testBindOverride(): void
    {
        $this->container->bind('foo', fn() => 'Foo');
        $this->container->bind('foo', fn() => 'Bar');

        self::assertEquals('Bar', $this->container->resolve('foo'));
    }

    public function testBindSharedWithClosure(): void
    {
        $this->container->bind('bar', fn() => new Bar(), true);

        $fooOne = $this->container->resolve('bar');
        $fooTwo = $this->container->resolve('bar');

        self::assertSame($fooOne, $fooTwo);
    }

    public function testBindSharedWithString(): void
    {
        $this->container->bind(alias: Bar::class, shared: true);

        $fooOne = $this->container->resolve(Bar::class);
        $fooTwo = $this->container->resolve(Bar::class);

        self::assertSame($fooOne, $fooTwo);
    }

    /* -------------------------------------------------
     * RESOLVE
     * -------------------------------------------------
     */

    public function testResolveAutoWiring(): void
    {
        $foo = $this->container->resolve(Foo::class);

        self::assertInstanceOf(Foo::class, $foo);
        self::assertInstanceOf(Bar::class, $foo->bar);
    }

    public function testResolveDependency(): void
    {
        $this->container->bind(BarInterface::class, Bar::class);

        $dependency = $this->container->resolve(Dependency::class);

        self::assertInstanceOf(Dependency::class, $dependency);
        self::assertInstanceOf(Bar::class, $dependency->bar);
    }

    public function testResolveNestedDependencies(): void
    {
        $this->container->bind(BarInterface::class, Bar::class);

        $nestedDependency = $this->container->resolve(NestedDependency::class);

        self::assertInstanceOf(Dependency::class, $nestedDependency->dependency);
        self::assertInstanceOf(Bar::class, $nestedDependency->dependency->bar);
    }

    public function testResolveWithDefaultValues(): void
    {
        $this->container->bind(Foo::class);
        $this->container->bind(BarInterface::class, Bar::class);

        $defaultValues = $this->container->resolve(DefaultValues::class);

        self::assertInstanceOf(DefaultValues::class, $defaultValues);
        self::assertInstanceOf(Foo::class, $defaultValues->foo);
        self::assertEquals('bar', $defaultValues->value);
    }

    public function testResolveVariadicPrimitive(): void
    {
        $variadicPrimitive = $this->container->resolve(VariadicPrimitive::class);

        $this->assertSame($variadicPrimitive->args, []);
    }

    public function testResolveThrowsExceptionOnVariadicDependencies(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        $this->container->resolve(VariadicObjects::class);
    }

    public function testResolveThrowsExceptionOnUnresolvableDependency(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        $this->container->resolve(UnresolvableDependency::class);
    }

    public function testResolveThrowsExceptionOnUnresolvableInstance(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        $this->container->resolve(BarInterface::class);
    }

    public function testResolveThrowsExceptionWhenClassNotExists(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        $this->container->resolve('Foo\Bar\Baz');
    }

    public function testResolveThrowsExceptionWhenConstructorIsProtected(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        $this->container->resolve(ProtectedConstructor::class);
    }

    /* -------------------------------------------------
     * GET
     * -------------------------------------------------
     */

    public function testGet(): void
    {
        self::assertInstanceOf(Foo::class, $this->container->get(Foo::class));
    }

    public function testGetThrowsExceptionWhenNotResolvable(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        $this->container->get(BarInterface::class);
    }

    public function testGetThrowsExceptionWhenClassNotExists(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        $this->container->bind('foo', 'Foo\Bar\Baz');
        $this->container->get('foo');
    }

    /* -------------------------------------------------
     * HAS
     * -------------------------------------------------
     */

    public function testHas(): void
    {
        $this->container->bind('foo', Foo::class);

        self::assertTrue($this->container->has('foo'));
        self::assertFalse($this->container->has('bar'));
    }

    /* -------------------------------------------------
     * IS SHARED
     * -------------------------------------------------
     */

    public function testIsShared(): void
    {
        $this->container->bind(Foo::class);
        $this->container->bind(alias: Bar::class, shared: true);

        self::assertFalse($this->container->isShared(Foo::class));
        self::assertTrue($this->container->isShared(Bar::class));
    }

    /* -------------------------------------------------
     * TAG
     * -------------------------------------------------
     */

    public function testTag(): void
    {
        $this->container->tag(TagOne::class, ['tagOne', 'tagTwo']);
        $this->container->tag(TagTwo::class, ['tagOne']);

        $tagOne = $this->container->tagged('tagOne');
        $tagTwo = $this->container->tagged('tagTwo');

        self::assertCount(2, $tagOne);
        self::assertCount(1, $tagTwo);

        $tagOneResults = [];
        foreach ($tagOne as $one) {
            $tagOneResults[] = $one;
        }

        $tagTwoResults = [];
        foreach ($tagTwo as $two) {
            $tagTwoResults[] = $two;
        }

        self::assertInstanceOf(TagOne::class, $tagOneResults[0]);
        self::assertInstanceOf(TagOne::class, $tagTwoResults[0]);
        self::assertInstanceof(TagTwo::class, $tagOneResults[1]);
    }

    public function testTagArrayAliases(): void
    {
        $this->container->tag([TagOne::class, TagTwo::class], ['tag']);

        $this->container->bind(TagAggregate::class, function ($container) {
            return new TagAggregate(...$container->tagged('tag'));
        });

        $result = $this->container->resolve(TagAggregate::class);

        self::assertCount(2, $result->tags);
        self::assertInstanceOf(TagOne::class, $result->tags[0]);
        self::assertInstanceOf(TagTwo::class, $result->tags[1]);
    }

    public function testTaggedLazyLoaded(): void
    {
        $container = $this->createPartialMock(Container::class, ['resolve']);
        $container->expects(self::once())
            ->method('resolve')
            ->willReturn(new TagOne());

        $container->tag(TagOne::class, ['tag']);
        $container->tag(TagTwo::class, ['tag']);

        $tagResults = [];
        foreach ($container->tagged('tag') as $tag) {
            $tagResults[] = $tag;
            break;
        }

        self::assertCount(2, $container->tagged('tag'));
        self::assertInstanceOf(TagOne::class, $tagResults[0]);
    }

    public function testTaggedLoopMultipleTimes(): void
    {
        $this->container->tag(TagOne::class, ['tag']);
        $this->container->tag(TagTwo::class, ['tag']);

        $tagResults = [];
        foreach ($this->container->tagged('tag') as $tag) {
            $tagResults[] = $tag;
        }

        self::assertCount(2, $tagResults);
        self::assertInstanceOf(TagOne::class, $tagResults[0]);
        self::assertInstanceOf(TagTwo::class, $tagResults[1]);

        $tagResults = [];
        foreach ($this->container->tagged('tag') as $tag) {
            $tagResults[] = $tag;
        }

        self::assertCount(2, $tagResults);
        self::assertInstanceOf(TagOne::class, $tagResults[0]);
        self::assertInstanceOf(TagTwo::class, $tagResults[1]);
    }

    public function testTaggedThrowsExceptionOnInvalidTag(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        $this->container->tagged('nope');
    }

    /* -------------------------------------------------
     * EXTEND
     * -------------------------------------------------
     */

    public function testExtend(): void
    {
        $this->container->bind('foo', fn() => 'Foo');

        self::assertEquals('Foo', $this->container->resolve('foo'));

        $this->container->extend('foo', fn($foo, Container $container) => $foo . 'Bar');

        self::assertSame('FooBar', $this->container->resolve('foo'));
    }

    public function testExtendSharedBinding(): void
    {
        $this->container->bind('foo', fn() => (object)['bar' => 'Bar'], true);
        $this->container->extend('foo', static function ($object) {
            $object->baz = 'Baz';

            return $object;
        });

        $result = $this->container->get('foo');

        self::assertSame('Bar', $result->bar);
        self::assertEquals('Baz', $result->baz);
        self::assertSame($result, $this->container->resolve('foo'));
    }

    public function testExtendMultiple(): void
    {
        $this->container->bind('foo', fn() => 'Foo');
        $this->container->extend('foo', fn($foo) => $foo . 'Bar');
        $this->container->extend('foo', fn($foo) => $foo . 'Baz');

        self::assertSame('FooBarBaz', $this->container->resolve('foo'));
    }

    public function testExtendBeforeBind(): void
    {
        $this->container->extend('foo', fn($foo) => $foo . 'Bar');
        $this->container->bind('foo', fn() => 'Foo');

        self::assertEquals('FooBar', $this->container->resolve('foo'));
    }

    public function testExtendLazy(): void
    {
        $this->container->bind(ExtendLazy::class);
        $this->container->extend(ExtendLazy::class, function ($lazy) {
            $lazy->init();

            return $lazy;
        });

        self::assertFalse(ExtendLazy::$init);

        $this->container->resolve(ExtendLazy::class);

        self::assertTrue(ExtendLazy::$init);
    }

    public function testExtendSharedBindingsAfterResolve(): void
    {
        $this->container->bind('foo', function () {
            $object = new stdClass();
            $object->foo = 'bar';

            return $object;
        }, true);

        $resultOne = $this->container->resolve('foo');

        $this->container->extend('foo', function ($object) {
            $object->foo = 'foo';

            return $object;
        });

        $resultTwo = $this->container->resolve('foo');

        self::assertSame($resultOne->foo, $resultTwo->foo);
    }
}
