<?php

declare(strict_types=1);

namespace Entropy\Router\Tests;

use Entropy\Router\RouterConfigFactory;
use Pg\Router\Router;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class RouterConfigFactoryTest extends TestCase
{
    private RouterConfigFactory $factory;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->factory = new RouterConfigFactory();
        $this->container = $this->createMock(ContainerInterface::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testReturnsNullWhenNoConfigNeeded(): void
    {
        $this->container->method('has')
            ->willReturn(false);

        $config = ($this->factory)($this->container);
        $this->assertNull($config);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testConfigWithCacheEnabled(): void
    {
        $this->container->method('has')
            ->willReturnMap([
                ['env', true],
                ['app.cache.dir', true],
                [ 'router.cache.pool.factory', false],
                [ 'router.tokens', false]
            ]);

        $this->container->method('get')
            ->willReturnMap([
                ['env', 'prod'],
                ['app.cache.dir', '/tmp']
            ]);

        $config = ($this->factory)($this->container);

        $this->assertTrue($config[Router::CONFIG_CACHE_ENABLED]);
        $this->assertEquals('/tmp/Router', $config[Router::CONFIG_CACHE_DIR]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testConfigWithCacheDisabledInDev(): void
    {
        $this->container->method('has')
            ->willReturnMap([
                ['env', true],
                ['app.cache.dir', true],
                [ 'router.cache.pool.factory', false],
                [ 'router.tokens', false]
            ]);

        $this->container->method('get')
            ->willReturnMap([
                ['env', 'dev'],
                ['app.cache.dir', '/tmp']
            ]);

        $config = ($this->factory)($this->container);
        $this->assertFalse($config[Router::CONFIG_CACHE_ENABLED]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testConfigWithCustomCachePoolFactory(): void
    {
        $cachePoolFactory = fn() => null;

        $this->container->method('has')
            ->willReturnMap([
                ['env', true],
                ['app.cache.dir', true],
                ['router.cache.pool.factory', true],
                [ 'router.tokens', false]
            ]);

        $this->container->method('get')
            ->willReturnMap([
                ['env', 'prod'],
                ['app.cache.dir', '/tmp'],
                ['router.cache.pool.factory', $cachePoolFactory]
            ]);

        $config = ($this->factory)($this->container);

        $this->assertSame($cachePoolFactory, $config[Router::CONFIG_CACHE_POOL_FACTORY]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testConfigWithCustomTokens(): void
    {
        $tokens = ['id' => '\d+'];

        $this->container->method('has')
            ->willReturnMap([
                ['env', false],
                ['app.cache.dir', false],
                ['router.cache.pool.factory', false],
                ['router.tokens', true]
            ]);

        $this->container->method('get')
            ->willReturnMap([
                ['router.tokens', $tokens]
            ]);

        $config = ($this->factory)($this->container);

        $this->assertEquals($tokens, $config[Router::CONFIG_DEFAULT_TOKENS]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testCacheConfigDisabledOnCacheDirException(): void
    {
        $this->container->method('has')
            ->willReturnMap([
                ['env', true],
                ['app.cache.dir', true],
                ['router.cache.pool.factory', false],
                [ 'router.tokens', false]
            ]);

        $this->container->method('get')
            ->willReturnCallback(function ($key) {
                if ($key === 'env') {
                    return 'prod';
                }
                if ($key === 'app.cache.dir') {
                    throw new \Exception('Cache dir error');
                }
                return null;
            });

        $config = ($this->factory)($this->container);

        $this->assertFalse($config[Router::CONFIG_CACHE_ENABLED]);
    }
}
