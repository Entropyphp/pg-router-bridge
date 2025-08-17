<?php

declare(strict_types=1);

namespace Entropy\Router\Tests\Middleware;

use Entropy\Router\Middleware\RouterMiddleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Pg\Router\Route;
use Pg\Router\RouteResult;
use Pg\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouterMiddlewareTest extends TestCase
{
    private RouterInterface $router;
    private RouterMiddleware $middleware;
    private RequestHandlerInterface $handler;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->middleware = new RouterMiddleware($this->router);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
    }

    public function testTrailingSlashRedirect(): void
    {
        $request = new ServerRequest('GET', new Uri('http://example.com/test/'));

        $response = $this->middleware->process($request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('/test', $response->getHeaderLine('Location'));
    }

    public function testMethodOverrideWithDelete(): void
    {
        $request = (new ServerRequest('POST', new Uri('http://example.com')))
            ->withParsedBody(['_method' => 'DELETE']);

        $result = $this->createMock(RouteResult::class);
        $result->method('isMethodFailure')->willReturn(false);
        $result->method('isFailure')->willReturn(false);

        $route = $this->createMock(Route::class);
        $result->method('getMatchedRoute')->willReturn($route);
        $result->method('getMatchedAttributes')->willReturn([]);

        $this->router->expects($this->once())
            ->method('match')
            ->with($this->callback(function ($request) {
                return $request->getMethod() === 'DELETE';
            }))
            ->willReturn($result);

        $this->handler->method('handle')->willReturn(new Response());

        $this->middleware->process($request, $this->handler);
    }

    public function testMethodFailure(): void
    {
        $request = new ServerRequest('GET', new Uri('http://example.com'));
        $result = $this->createMock(RouteResult::class);
        $result->method('isMethodFailure')->willReturn(true);

        $this->router->method('match')->willReturn($result);
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function ($request) use ($result) {
                return $request->getAttribute(get_class($result)) === $result;
            }))
            ->willReturn(new Response());

        $this->middleware->process($request, $this->handler);
    }

    public function testRouteFailure(): void
    {
        $request = new ServerRequest('GET', new Uri('http://example.com'));
        $result = $this->createMock(RouteResult::class);
        $result->method('isMethodFailure')->willReturn(false);
        $result->method('isFailure')->willReturn(true);

        $this->router->method('match')->willReturn($result);
        $this->handler->expects($this->once())
            ->method('handle')
            ->willReturn(new Response());

        $this->middleware->process($request, $this->handler);
    }

    public function testSuccessfulRoute(): void
    {
        $request = new ServerRequest('GET', new Uri('http://example.com'));
        $result = $this->createMock(RouteResult::class);
        $result->method('isMethodFailure')->willReturn(false);
        $result->method('isFailure')->willReturn(false);

        $route = $this->createMock(Route::class);
        $callback = function () {
            return new Response();
        };
        $route->method('getCallback')->willReturn($callback);
        $params = ['id' => '1'];

        $result->method('getMatchedRoute')->willReturn($route);
        $result->method('getMatchedAttributes')->willReturn($params);

        $this->router->method('match')->willReturn($result);
        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function ($request) use ($callback, $params, $result) {
                return $request->getAttribute('_controller') === $callback
                    && $request->getAttribute('_params') === $params
                    && $request->getAttribute('id') === '1'
                    && $request->getAttribute(get_class($result)) === $result;
            }))
            ->willReturn(new Response());

        $this->middleware->process($request, $this->handler);
    }
}
