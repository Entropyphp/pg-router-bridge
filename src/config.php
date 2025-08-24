<?php

declare(strict_types=1);

use Entropy\Router\Command\RouteListCommand;
use Entropy\Router\RouterConfigFactory;
use Entropy\Router\RouterFactory;
use Pg\Router\Router;
use Pg\Router\RouterInterface;

use function DI\add;
use function DI\factory;

return [
    RouterInterface::class => factory(RouterFactory::class),
    Router::class => factory(RouterFactory::class),
    'router.config' => RouterConfigFactory::class,
    'console.commands' => add([
        'route:list' => RouteListCommand::class,
    ]),
    'router.tokens' => add([
        'id' => '[0-9]+',
        'slug' => '[a-zA-Z-]+[a-zA-Z0-9_-]+',
    ]),
];
