<?php

declare(strict_types=1);

namespace Zaphyr\ContainerTests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use stdClass;
use Zaphyr\Container\Container;
use Zaphyr\ContainerTests\TestAssets\Bar;
use Zaphyr\ContainerTests\TestAssets\BarInterface;
use Zaphyr\ContainerTests\TestAssets\Call;
use Zaphyr\ContainerTests\TestAssets\CallInvoke;
use Zaphyr\ContainerTests\TestAssets\DefaultValues;
use Zaphyr\ContainerTests\TestAssets\Dependency;
use Zaphyr\ContainerTests\TestAssets\ExtendLazy;
use Zaphyr\ContainerTests\TestAssets\Foo;
use Zaphyr\ContainerTests\TestAssets\NestedDependency;
use Zaphyr\ContainerTests\TestAssets\ProtectedConstructor;
use Zaphyr\ContainerTests\TestAssets\ServiceProvider;
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

        self::assertInstanceOf(Foo::class, $foo = $this->container->get(Foo::class));
        self::assertInstanceOf(Bar::class, $foo->bar);
    }

    public function testBindWithStringConcrete(): void
    {
        $this->container->bind(BarInterface::class, Bar::class);

        self::assertInstanceOf(Bar::class, $this->container->get(BarInterface::class));
    }

    public function testBindWithClosureConcrete(): void
    {
        $this->container->bind('foo', fn() => 'Foo');

        self::assertEquals('Foo', $this->container->get('foo'));
    }

    public function testBindContainerIsPassedToClosure(): void
    {
        $this->container->bind('foo', fn(Container $container) => $container);

        self::assertInstanceOf(Container::class, $this->container->get('foo'));
    }

    public function testBindOverride(): void
    {
        $this->container->bind('foo', fn() => 'Foo');
        $this->container->bind('foo', fn() => 'Bar');

        self::assertEquals('Bar', $this->container->get('foo'));
    }

    /* -------------------------------------------------
     * BIND SINGLETON
     * -------------------------------------------------
     */

    public function testBindSingletonWithClosure(): void
    {
        $this->container->bindSingleton('bar', fn() => new Bar());

        $fooOne = $this->container->get('bar');
        $fooTwo = $this->container->get('bar');

        self::assertSame($fooOne, $fooTwo);
    }

    public function testBindSingletonWithString(): void
    {
        $this->container->bindSingleton(Bar::class);

        $fooOne = $this->container->get(Bar::class);
        $fooTwo = $this->container->get(Bar::class);

        self::assertSame($fooOne, $fooTwo);
    }

    /* -------------------------------------------------
     * GET
     * -------------------------------------------------
     */

    public function testGetAutoWiring(): void
    {
        $foo = $this->container->get(Foo::class);

        self::assertInstanceOf(Foo::class, $foo);
        self::assertInstanceOf(Bar::class, $foo->bar);
    }

    public function testGetDependency(): void
    {
        $this->container->bind(BarInterface::class, Bar::class);

        $dependency = $this->container->get(Dependency::class);

        self::assertInstanceOf(Dependency::class, $dependency);
        self::assertInstanceOf(Bar::class, $dependency->bar);
    }

    public function testGetNestedDependencies(): void
    {
        $this->container->bind(BarInterface::class, Bar::class);

        $nestedDependency = $this->container->get(NestedDependency::class);

        self::assertInstanceOf(Dependency::class, $nestedDependency->dependency);
        self::assertInstanceOf(Bar::class, $nestedDependency->dependency->bar);
    }

    public function testGetWithDefaultValues(): void
    {
        $this->container->bind(Foo::class);
        $this->container->bind(BarInterface::class, Bar::class);

        $defaultValues = $this->container->get(DefaultValues::class);

        self::assertInstanceOf(DefaultValues::class, $defaultValues);
        self::assertInstanceOf(Foo::class, $defaultValues->foo);
        self::assertEquals('bar', $defaultValues->value);
    }

    public function testGetVariadicPrimitive(): void
    {
        $variadicPrimitive = $this->container->get(VariadicPrimitive::class);

        $this->assertSame($variadicPrimitive->args, []);
    }

    public function testGetThrowsExceptionOnVariadicDependencies(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        $this->container->get(VariadicObjects::class);
    }

    public function testGetThrowsExceptionOnUnresolvableDependency(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        $this->container->get(UnresolvableDependency::class);
    }

    public function testGetThrowsExceptionOnUnresolvableInstance(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        $this->container->get(BarInterface::class);
    }

    public function testGetThrowsExceptionWhenClassNotExists(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        $this->container->get('Foo\Bar\Baz');
    }

    public function testGetThrowsExceptionWhenConstructorIsProtected(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        $this->container->get(ProtectedConstructor::class);
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
     * CALL
     * -------------------------------------------------
     */

    public function testCallClassName(): void
    {
        $inject = $this->container->call([Call::class, 'inject']);

        self::assertInstanceOf(Foo::class, $inject[0]);
        self::assertEquals('foo', $inject[1]);
    }

    public function testCallObject(): void
    {
        $inject = $this->container->call([new Call(), 'inject']);

        self::assertInstanceOf(Foo::class, $inject[0]);
        self::assertEquals('foo', $inject[1]);
    }

    public function testCallClosure(): void
    {
        $foo = $this->container->call(fn(Foo $foo) => $foo);

        self::assertInstanceOf(Foo::class, $foo);
        self::assertInstanceOf(Bar::class, $foo->bar);
    }

    public function testCallStaticClassStringMethodName(): void
    {
        $inject = $this->container->call(Call::class . '::inject');

        self::assertInstanceOf(Foo::class, $inject[0]);
        self::assertEquals('foo', $inject[1]);
    }

    public function testCallStaticMethod(): void
    {
        $inject = $this->container->call([Call::class, 'injectStatic']);

        self::assertInstanceOf(Foo::class, $inject[0]);
        self::assertEquals('foo', $inject[1]);
    }

    public function testCallParameters(): void
    {
        $injectParams = $this->container->call([new Call(), 'injectParams'], ['foo', 'bar']);

        self::assertEquals(['foo', 'bar'], $injectParams);
    }

    public function testCallInvoke(): void
    {
        $invoke = $this->container->call(CallInvoke::class);

        self::assertInstanceOf(Foo::class, $invoke[0]);
        self::assertEquals('foo', $invoke[1]);
        self::assertInstanceOf(Call::class, $invoke[2]);
    }

    public function testCallGlobalMethod(): void
    {
        $inject = $this->container->call('Zaphyr\ContainerTests\TestAssets\callFunction');

        self::assertInstanceOf(Foo::class, $inject[0]);
        self::assertEquals('foo', $inject[1]);
    }

    public function testCallDependencies(): void
    {
        $result = $this->container->call(function (stdClass $foo, $bar = []) {
            return [$foo, $bar];
        });

        self::assertInstanceOf(stdClass::class, $result[0]);
        self::assertEquals([], $result[1]);

        $result = $this->container->call(function (stdClass $foo, $bar = []) {
            return [$foo, $bar];
        }, ['bar' => 'bar']);

        self::assertInstanceOf(stdClass::class, $result[0]);
        self::assertEquals('bar', $result[1]);

        $bar = new Bar();
        $result = $this->container->call(function (stdClass $foo, Bar $bar) {
            return [$foo, $bar];
        }, [Bar::class => $bar]);

        self::assertInstanceOf(stdClass::class, $result[0]);
        self::assertSame($bar, $result[1]);
    }

    public function testCallVariadicDependency(): void
    {
        $barOne = new Bar();
        $barTwo = new Bar();

        $this->container->bind(Bar::class, function () use ($barOne, $barTwo) {
            return [$barOne, $barTwo];
        });

        $result = $this->container->call(function (stdClass $foo, Bar ...$bar) {
            return func_get_args();
        });

        self::assertInstanceOf(stdClass::class, $result[0]);
        self::assertInstanceOf(Bar::class, $result[1]);
        self::assertSame($barOne, $result[1]);
        self::assertSame($barTwo, $result[2]);
    }

    public function testCallThrowsExceptionWhenClassNotExists(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        $this->container->call('Foo\Bar\Baz::inject');
    }

    public function testCallThrowsExceptionOnUnresolvable(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        $this->container->call([Call::class, 'injectUnresolvable']);
    }

    public function testCallThrowsExceptionOnUnnamedParameters(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        $this->container->call([Call::class, 'injectUnresolvable'], ['foo', 'bar']);
    }

    public function testCallThrowsExceptionOnMissingParameters(): void
    {
        $this->expectException(ContainerExceptionInterface::class);

        $this->container->call(function ($foo, $bar = 'default') {
            return $foo;
        });
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

        $result = $this->container->get(TagAggregate::class);

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

        self::assertEquals('Foo', $this->container->get('foo'));

        $this->container->extend('foo', fn($foo, Container $container) => $foo . 'Bar');

        self::assertSame('FooBar', $this->container->get('foo'));
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
        self::assertSame($result, $this->container->get('foo'));
    }

    public function testExtendMultiple(): void
    {
        $this->container->bind('foo', fn() => 'Foo');
        $this->container->extend('foo', fn($foo) => $foo . 'Bar');
        $this->container->extend('foo', fn($foo) => $foo . 'Baz');

        self::assertSame('FooBarBaz', $this->container->get('foo'));
    }

    public function testExtendBeforeBind(): void
    {
        $this->container->extend('foo', fn($foo) => $foo . 'Bar');
        $this->container->bind('foo', fn() => 'Foo');

        self::assertEquals('FooBar', $this->container->get('foo'));
    }

    public function testExtendLazy(): void
    {
        $this->container->bind(ExtendLazy::class);
        $this->container->extend(ExtendLazy::class, function ($lazy) {
            $lazy->init();

            return $lazy;
        });

        self::assertFalse(ExtendLazy::$init);

        $this->container->get(ExtendLazy::class);

        self::assertTrue(ExtendLazy::$init);
    }

    public function testExtendSharedBindingsAfterResolve(): void
    {
        $this->container->bind('foo', function () {
            $object = new stdClass();
            $object->foo = 'bar';

            return $object;
        }, true);

        $resultOne = $this->container->get('foo');

        $this->container->extend('foo', function ($object) {
            $object->foo = 'foo';

            return $object;
        });

        $resultTwo = $this->container->get('foo');

        self::assertSame($resultOne->foo, $resultTwo->foo);
    }

    /* -------------------------------------------------
     * SERVICE PROVIDER
     * -------------------------------------------------
     */

    public function testServiceProviderRegister(): void
    {
        $this->container->registerServiceProvider(new ServiceProvider());

        self::assertTrue($this->container->has(Foo::class));
        self::assertInstanceOf(Foo::class, $this->container->get(Foo::class));
    }

    public function testServiceProviderThrowsExceptionWhenNotResolvable(): void
    {
        $this->container->registerServiceProvider(new ServiceProvider());
        self::assertTrue($this->container->has('liar'));

        $this->expectException(ContainerExceptionInterface::class);

        $this->container->get('liar');
    }

    public function testExtendServiceProviderInstance(): void
    {
        $this->container->registerServiceProvider(new ServiceProvider());

        self::assertSame('foo', $this->container->get('value'));

        $this->container->extend('value', fn($foo, Container $container) => $foo . 'Bar');

        self::assertSame('fooBar', $this->container->get('value'));
    }
}
