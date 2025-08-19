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
        $config = null;
        if ($container->has('router.config')) {
            $config = $container->get('router.config');
        }

        $regexCollector = null;
        if ($container->has('router.regex.collector')) {
            $regexCollector = $container->get('router.regex.collector');
        }
        $matcherFactory = null;
        if ($container->has('router.matcher.factory')) {
            $matcherFactory = $container->get('router.matcher.factory');
        }
        return new Router(
            $regexCollector, // MarkRegexCollector
            $matcherFactory, // MarkDataMatcher factory
            $config
        );
    }
}
