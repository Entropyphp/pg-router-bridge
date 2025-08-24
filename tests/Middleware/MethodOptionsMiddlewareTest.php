<?php

declare(strict_types=1);

namespace Entropy\Router\Tests\Middleware;

use Entropy\Router\Middleware\MethodOptionsMiddleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Pg\Router\Route;
use Pg\Router\RouteResult;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MethodOptionsMiddlewareTest extends TestCase
{
    private MethodOptionsMiddleware $middleware;
    private RequestHandlerInterface $handler;
    private ServerRequest $request;

    protected function setUp(): void
    {
        $this->middleware = new MethodOptionsMiddleware();
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->request = new ServerRequest('OPTIONS', new Uri('https://example.com'));
    }

    public function testNonOptionsRequestPassesThrough(): void
    {
        $request = new ServerRequest('GET', new Uri('https://example.com'));
        $expectedResponse = new Response();

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $this->handler);
        $this->assertSame($expectedResponse, $response);
    }

    public function testOptionsRequestWithoutRouteResultPassesThrough(): void
    {
        $expectedResponse = new Response();

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($this->request, $this->handler);
        $this->assertSame($expectedResponse, $response);
    }

    public function testOptionsRequestWithRouteFailurePassesThrough(): void
    {
        $result = $this->createMock(RouteResult::class);
        $result->method('isFailure')->willReturn(true);
        $result->method('isMethodFailure')->willReturn(false);

        $request = $this->request->withAttribute(RouteResult::class, $result);
        $expectedResponse = new Response();

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $this->handler);
        $this->assertSame($expectedResponse, $response);
    }

    public function testOptionsRequestWithMatchedRoutePassesThrough(): void
    {
        $result = $this->createMock(RouteResult::class);
        $result->method('isFailure')->willReturn(false);
        $result->method('getMatchedRoute')->willReturn($this->createMock(Route::class));

        $request = $this->request->withAttribute(RouteResult::class, $result);
        $expectedResponse = new Response();

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $this->handler);
        $this->assertSame($expectedResponse, $response);
    }

    public function testOptionsRequestWithMethodFailure(): void
    {
        $allowedMethods = ['GET', 'POST'];
        $result = $this->createMock(RouteResult::class);
        $result->method('isFailure')->willReturn(true);
        $result->method('isMethodFailure')->willReturn(true);
        $result->method('getAllowedMethods')->willReturn($allowedMethods);

        $request = $this->request->withAttribute(RouteResult::class, $result);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($allowedMethods, $response->getHeader('Access-Control-Allow-Methods'));
        $this->assertEquals('https://example.com', $response->getHeader('Access-Control-Allow-Origin')[0]);
        $this->assertEquals('true', $response->getHeader('Access-Control-Allow-Credentials')[0]);
        $this->assertStringContainsString('application/json', $response->getHeader('Content-Type')[0]);
    }

    public function testOptionsRequestWithOriginHeader(): void
    {
        $origin = 'https://test.com';
        $result = $this->createMock(RouteResult::class);
        $result->method('isFailure')->willReturn(true);
        $result->method('isMethodFailure')->willReturn(true);
        $result->method('getAllowedMethods')->willReturn(['GET']);

        $request = $this->request
            ->withAttribute(RouteResult::class, $result)
            ->withHeader('Origin', $origin);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertEquals($origin, $response->getHeader('Access-Control-Allow-Origin')[0]);
    }
}
