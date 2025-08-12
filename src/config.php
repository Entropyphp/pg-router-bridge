<?php

declare(strict_types=1);

use Entropy\Router\Command\RouteListCommand;
use Entropy\Router\RouterFactory;
use Pg\Router\RouterInterface;

use function DI\add;
use function DI\factory;

return [
    RouterInterface::class => factory(RouterFactory::class),
    'console.commands' => add([
        'route:list' => RouteListCommand::class,
    ]),
];
