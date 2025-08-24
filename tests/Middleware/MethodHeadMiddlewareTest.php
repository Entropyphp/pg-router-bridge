<?php

declare(strict_types=1);

namespace Entropy\Router\Tests\Middleware;

use Entropy\Router\Middleware\MethodHeadMiddleware;
use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Pg\Router\Route;
use Pg\Router\RouteResult;
use Pg\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class MethodHeadMiddlewareTest extends TestCase
{
    private RouterInterface $router;
    private MethodHeadMiddleware $middleware;
    private RequestHandlerInterface $handler;
    private ServerRequest $request;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->middleware = new MethodHeadMiddleware($this->router);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->request = new ServerRequest('HEAD', new Uri('https://example.com/test'));
    }

    public function testNonHeadRequestPassesThrough(): void
    {
        $request = new ServerRequest('GET', new Uri('https://example.com/test'));
        $expectedResponse = new Response();

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $this->handler);
        $this->assertSame($expectedResponse, $response);
    }

    public function testHeadRequestWithoutRouteResultPassesThrough(): void
    {
        $expectedResponse = new Response();

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($this->request, $this->handler);
        $this->assertSame($expectedResponse, $response);
    }

    public function testHeadRequestWithMatchedRoutePassesThrough(): void
    {
        $result = $this->createMock(RouteResult::class);
        $route = $this->createMock(Route::class);
        $result->method('getMatchedRoute')->willReturn($route);

        $request = $this->request->withAttribute(RouteResult::class, $result);
        $expectedResponse = new Response();

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $this->handler);
        $this->assertSame($expectedResponse, $response);
    }

    public function testHeadRequestWithFailedGetRoutePassesThrough(): void
    {
        $result = $this->createMock(RouteResult::class);
        $result->method('getMatchedRoute')->willReturn(null);

        $getResult = $this->createMock(RouteResult::class);
        $getResult->method('isFailure')->willReturn(true);

        $this->router->expects($this->once())
            ->method('match')
            ->with($this->callback(function ($request) {
                return $request->getMethod() === RequestMethodInterface::METHOD_GET;
            }))
            ->willReturn($getResult);

        $request = $this->request->withAttribute(RouteResult::class, $result);
        $expectedResponse = new Response();

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $this->handler);
        $this->assertSame($expectedResponse, $response);
    }

    public function testSuccessfulHeadRequest(): void
    {
        $result = $this->createMock(RouteResult::class);
        $result->method('getMatchedRoute')->willReturn(null);

        $getResult = $this->createMock(RouteResult::class);
        $getResult->method('isFailure')->willReturn(false);
        $getResult->method('getMatchedAttributes')->willReturn(['id' => '123']);

        $this->router->expects($this->once())
            ->method('match')
            ->willReturn($getResult);

        $request = $this->request->withAttribute(RouteResult::class, $result);
        $response = new Response(200, [], 'some content');

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function ($request) use ($getResult) {
                return $request->getMethod() === RequestMethodInterface::METHOD_GET
                    && $request->getAttribute('id') === '123'
                    && $request->getAttribute(RouteResult::class) === $getResult
                    && $request->getAttribute(
                        MethodHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE
                    ) === RequestMethodInterface::METHOD_HEAD;
            }))
            ->willReturn($response);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('', (string)$response->getBody());
        $this->assertEquals(0, $response->getBody()->getSize());
    }
}
