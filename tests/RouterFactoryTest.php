<?php

declare(strict_types=1);

namespace Entropy\Router\Tests;

use Entropy\Router\RouterFactory;
use Pg\Router\Matcher\MarkDataMatcher;
use Pg\Router\RegexCollector\MarkRegexCollector;
use Pg\Router\Router;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

class RouterFactoryTest extends TestCase
{
    private RouterFactory $factory;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->factory = new RouterFactory();
        $this->container = $this->createMock(ContainerInterface::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws CacheException
     */
    public function testCreateRouterWithoutDependencies(): void
    {
        $this->container->method('has')
            ->willReturnMap([
                ['router.config', false],
                ['router.regex.collector', false],
                ['router.matcher.factory', false]
            ]);

        $router = ($this->factory)($this->container);

        $this->assertInstanceOf(Router::class, $router);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws CacheException
     */
    public function testCreateRouterWithConfig(): void
    {
        $config = [
            Router::CONFIG_CACHE_ENABLED => true,
            Router::CONFIG_CACHE_DIR => '/tmp/cache'
        ];

        $this->container->method('has')
            ->willReturnMap([
                ['router.config', true],
                ['router.regex.collector', false],
                ['router.matcher.factory', false]
            ]);

        $this->container->method('get')
            ->with('router.config')
            ->willReturn($config);

        $router = ($this->factory)($this->container);

        $this->assertInstanceOf(Router::class, $router);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws CacheException
     */
    public function testCreateRouterWithRegexCollector(): void
    {
        $regexCollector = $this->createMock(MarkRegexCollector::class);

        $this->container->method('has')
            ->willReturnMap([
                ['router.config', false],
                ['router.regex.collector', true],
                ['router.matcher.factory', false]
            ]);

        $this->container->method('get')
            ->with('router.regex.collector')
            ->willReturn($regexCollector);

        $router = ($this->factory)($this->container);

        $this->assertInstanceOf(Router::class, $router);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws CacheException
     */
    public function testCreateRouterWithMatcherFactory(): void
    {
        $matcherFactory = fn() => $this->createMock(MarkDataMatcher::class);

        $this->container->method('has')
            ->willReturnMap([
                ['router.config', false],
                ['router.regex.collector', false],
                ['router.matcher.factory', true]
            ]);

        $this->container->method('get')
            ->with('router.matcher.factory')
            ->willReturn($matcherFactory);

        $router = ($this->factory)($this->container);

        $this->assertInstanceOf(Router::class, $router);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws CacheException
     */
    public function testCreateRouterWithAllDependencies(): void
    {
        $config = [Router::CONFIG_CACHE_ENABLED => true];
        $regexCollector = $this->createMock(MarkRegexCollector::class);
        $matcherFactory = fn() => $this->createMock(MarkDataMatcher::class);

        $this->container->method('has')
            ->willReturnMap([
                ['router.config', true],
                ['router.regex.collector', true],
                ['router.matcher.factory', true]
            ]);

        $this->container->method('get')
            ->willReturnMap([
                ['router.config', $config],
                ['router.regex.collector', $regexCollector],
                ['router.matcher.factory', $matcherFactory]
            ]);

        $router = ($this->factory)($this->container);

        $this->assertInstanceOf(Router::class, $router);
    }
}
