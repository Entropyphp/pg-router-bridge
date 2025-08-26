<?php

declare(strict_types=1);

namespace Entropy\Router;

use Pg\Router\Router;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class RouterConfigFactory
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): null|array
    {
        $cacheEnable = false;
        if ($container->has('env')) {
            $cacheEnable = $container->get('env') === 'prod';
        }

        $config = null;
        if ($cacheEnable || $container->has('app.cache.dir')) {
            try {
                $cacheDir = $container->get('app.cache.dir');
            } catch (\Throwable) {
                $cacheDir = null;
                $cacheEnable = false;
            }
            $config = [
                Router::CONFIG_CACHE_ENABLED => $cacheEnable,
                Router::CONFIG_CACHE_DIR => $cacheDir . '/Router',
            ];
            if ($container->has('router.cache.pool.factory')) {
                $config[Router::CONFIG_CACHE_POOL_FACTORY] = $container->get('router.cache.pool.factory');
            }
        }

        if ($container->has('router.tokens')) {
            $config[Router::CONFIG_DEFAULT_TOKENS] = $container->get('router.tokens');
        }

        return $config;
    }
}
