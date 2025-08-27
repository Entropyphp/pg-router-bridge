<?php

namespace Entropy\Router\Tests\Command;

use Entropy\Router\Command\RouteListCommand;
use Pg\Router\Route;
use Pg\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class RouteListCommandTest extends TestCase
{
    public function testCommandListsRoutes()
    {
        // Mock Route
        $route = $this->createMock(Route::class);
        $route->method('getPath')->willReturn('/foo');
        $route->method('getCallback')->willReturn('FooController::bar');
        $route->method('getAllowedMethods')->willReturn(['GET', 'POST']);

        // Mock RouterInterface
        $router = $this->createMock(RouterInterface::class);
        $router->method('getRoutes')->willReturn([
            'foo_route' => $route,
        ]);

        // Instantiate command
        $command = new RouteListCommand($router);

        // Use CommandTester to execute
        $tester = new CommandTester($command);
        $tester->execute([]);

        $output = $tester->getDisplay();

        $this->assertStringContainsString('foo_route', $output);
        $this->assertStringContainsString('/foo', $output);
        $this->assertStringContainsString('FooController::bar', $output);
        $this->assertStringContainsString('GET', $output);
        $this->assertStringContainsString('POST', $output);
    }
}