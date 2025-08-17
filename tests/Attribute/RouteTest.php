<?php

declare(strict_types=1);

namespace Entropy\Router\Tests\Attribute;

use Entropy\Router\Attribute\Exception\RouteAttributeException;
use Entropy\Router\Attribute\Route;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase
{
    public function testCreateRouteWithPath(): void
    {
        $route = new Route(path: '/test');

        $this->assertEquals('/test', $route->getPath());
        $this->assertNull($route->getName());
        $this->assertNull($route->getHost());
        $this->assertNull($route->getMethods());
        $this->assertNull($route->getSchemes());
        $this->assertEquals([], $route->getMiddlewares());
    }

    public function testCreateRouteWithAllParameters(): void
    {
        $route = new Route(
            path: '/test/{id}',
            name: 'test.route',
            host: 'example.com',
            port: '8080',
            methods: ['GET', 'POST'],
            schemes: ['https'],
            middlewares: ['TestMiddleware']
        );

        $this->assertEquals('/test/{id}', $route->getPath());
        $this->assertEquals('test.route', $route->getName());
        $this->assertEquals('example.com', $route->getHost());
        $this->assertEquals('8080', $route->getPort());
        $this->assertEquals(['GET', 'POST'], $route->getMethods());
        $this->assertEquals(['https'], $route->getSchemes());
        $this->assertEquals(['TestMiddleware'], $route->getMiddlewares());
    }

    public function testCreateRouteWithParametersArray(): void
    {
        $route = new Route([
            'value' => '/test',
            'name' => 'test.route',
            'host' => 'example.com',
            'port' => '8080',
            'methods' => ['GET'],
            'schemes' => ['https']
        ]);

        $this->assertEquals('/test', $route->getPath());
        $this->assertEquals('test.route', $route->getName());
        $this->assertEquals('example.com', $route->getHost());
        $this->assertEquals('8080', $route->getPort());
        $this->assertEquals(['GET'], $route->getMethods());
        $this->assertEquals(['https'], $route->getSchemes());
    }

    public function testCreateRouteWithStringParameter(): void
    {
        $route = new Route('/test', name: 'test.route');

        $this->assertEquals('/test', $route->getPath());
        $this->assertEquals('test.route', $route->getName());
    }

    public function testCreateRouteWithoutPathThrowsException(): void
    {
        $this->expectException(RouteAttributeException::class);
        new Route([]);
    }

    public function testSetPort(): void
    {
        $route = new Route('/test');
        $route->setPort('9000');

        $this->assertEquals('9000', $route->getPort());
    }

    public function testGetParameters(): void
    {
        $params = [
            'value' => '/test',
            'name' => 'test.route'
        ];
        $route = new Route($params);

        $this->assertEquals($params, $route->getParameters());
    }

    public function testGetParametersWithStringParameter(): void
    {
        $route = new Route('/test');
        $this->assertEquals(['value' => '/test'], $route->getParameters());
    }

    public function testCreateRouteWithNullOptionalParameters(): void
    {
        $route = new Route(
            path: '/test',
            name: null,
            host: null,
            port: null,
            methods: [],
            schemes: []
        );

        $this->assertEquals('/test', $route->getPath());
        $this->assertNull($route->getName());
        $this->assertNull($route->getHost());
        $this->assertNull($route->getMethods());
        $this->assertNull($route->getSchemes());
    }
}
