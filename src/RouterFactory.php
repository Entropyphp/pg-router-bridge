<?php

/** @see       https://github.com/willy68/pg-router for the canonical source repository */

declare(strict_types=1);

namespace Entropy\Router;

use Pg\Router\Router;
use Psr\Cache\CacheException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Create and return an instance of Pg-Router.
 *
 * Configuration should look like the following:
 *
 * <code>
 * $router = new Router(
 * null,
 * null,
 * [
 * Router::CONFIG_CACHE_ENABLED => ($env === 'prod'),
 * Router::CONFIG_CACHE_DIR => '/tmp/cache',
 * Router::CONFIG_CACHE_POOL_FACTORY => function (): CacheItemPoolInterface {...},
 * Router::CONFIG_DEFAULT_TOKENS => ['id' => '[0-9]+', 'slug' => '[a-zA-Z-]+[a-zA-Z0-9_-]+'],
 * ]);
 * </code>
 */
class RouterFactory
{
    /**
     * @param ContainerInterface $container
     * @return Router
     * @throws CacheException
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): Router
    {
        $cacheEnable = false;
        if ($container->has('env')) {
            $cacheEnable = $container->get('env') === 'prod';
        }


        $config = null;
        if ($cacheEnable && $container->has('app.cache.dir')) {
            try {
                $cacheDir = $container->get('app.cache.dir');
            } catch (\Throwable) {
                $cacheDir = null;
                $cacheEnable = false;
            }
            $config = [
                Router::CONFIG_CACHE_ENABLED => $cacheEnable,
                Router::CONFIG_CACHE_DIR => $cacheDir . '/Router',
                Router::CONFIG_CACHE_POOL_FACTORY => null,
                Router::CONFIG_DEFAULT_TOKENS => ['id' => '[0-9]+', 'slug' => '[a-zA-Z-]+[a-zA-Z0-9_-]+'],
            ];
        }

        return new Router(
            null, // MarkRegexCollector
            null, // MarkDataMatcher factory
            $config
        );
    }
}
