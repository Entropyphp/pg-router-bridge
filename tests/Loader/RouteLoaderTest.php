<?php

declare(strict_types=1);

namespace Entropy\Router\Tests\Loader;

use Entropy\Router\Attribute\Exception\RouteAttributeException;
use Entropy\Router\Attribute\Route as RouteAttribute;
use Entropy\Router\Loader\RouteLoader;
use Entropy\Utils\Attribute\AttributeLoader;
use Pg\Router\Route;
use Pg\Router\RouterInterface;
use PHPUnit\Framework\TestCase;

class RouteLoaderTest extends TestCase
{
    private RouterInterface $router;
    private AttributeLoader $attributeLoader;
    private RouteLoader $routeLoader;
    private Route $route;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->attributeLoader = $this->createMock(AttributeLoader::class);
        $this->route = $this->createMock(Route::class);
        $this->routeLoader = new RouteLoader($this->router, $this->attributeLoader);
    }

    public function testLoadNonExistentClass(): void
    {
        $this->assertNull($this->routeLoader->load('NonExistentClass'));
    }

    public function testLoadAbstractClass(): void
    {
        $this->assertNull($this->routeLoader->load(AbstractTestController::class));
    }

    public function testLoadClassWithoutAttributes(): void
    {
        $this->attributeLoader->method('getClassAttribute')
            ->willReturn(null);
        $this->attributeLoader->method('getMethodAttributes')
            ->willReturn([]);

        $this->assertNull($this->routeLoader->load(TestController::class));
    }

    /**
     * @throws RouteAttributeException
     */
    public function testLoadClassWithMethodAttribute(): void
    {
        $methodAttribute = new RouteAttribute('/test', name: 'test', methods: ['GET']);
        $this->attributeLoader->method('getClassAttribute')
            ->willReturn(null);
        $this->attributeLoader->method('getMethodAttributes')
            ->willReturn([$methodAttribute]);

        $this->route->method('setSchemes')->willReturnSelf();
        $this->route->method('setHost')->willReturnSelf();
        $this->route->method('setPort')->willReturnSelf();
        $this->route->method('middlewares')->willReturnSelf();

        $this->router->expects($this->once())
            ->method('route')
            ->with('/test', TestController::class . '::testMethod', 'test', ['GET'])
            ->willReturn($this->route);

        $routes = $this->routeLoader->load(TestController::class);
        $this->assertCount(1, $routes);
    }

    /**
     * @throws RouteAttributeException
     */
    public function testLoadClassWithClassAndMethodAttribute(): void
    {
        $classAttribute = new RouteAttribute(path: '/api', name: 'api');
        $methodAttribute = new RouteAttribute(path: '/test', name: 'test', methods: ['GET']);

        $this->attributeLoader->method('getClassAttribute')
            ->willReturn($classAttribute);
        $this->attributeLoader->method('getMethodAttributes')
            ->willReturn([$methodAttribute]);

        $this->route->method('setSchemes')->willReturnSelf();
        $this->route->method('setHost')->willReturnSelf();
        $this->route->method('setPort')->willReturnSelf();
        $this->route->method('middlewares')->willReturnSelf();

        $this->router->expects($this->once())
            ->method('route')
            ->with('/api/test', TestController::class . '::testMethod', 'test', ['GET'])
            ->willReturn($this->route);

        $routes = $this->routeLoader->load(TestController::class);
        $this->assertCount(1, $routes);
    }

    /**
     * @throws RouteAttributeException
     */
    public function testLoadInvokableClassWithClassAttribute(): void
    {
        $classAttribute = new RouteAttribute(path: '/api', name: 'api', methods: ['GET']);

        $this->attributeLoader->method('getClassAttribute')
            ->willReturn($classAttribute);
        $this->attributeLoader->method('getMethodAttributes')
            ->willReturn([]);
        $this->attributeLoader->method('getClassAttributes')
            ->willReturn([$classAttribute]);

        $this->route->method('setSchemes')->willReturnSelf();
        $this->route->method('setHost')->willReturnSelf();
        $this->route->method('setPort')->willReturnSelf();
        $this->route->method('middlewares')->willReturnSelf();

        $this->router->expects($this->once())
            ->method('route')
            ->with('/api', InvokableTestController::class, 'api', ['GET'])
            ->willReturn($this->route);

        $routes = $this->routeLoader->load(InvokableTestController::class);
        $this->assertCount(1, $routes);
    }
}
