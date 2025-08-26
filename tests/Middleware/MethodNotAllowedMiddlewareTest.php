<?php

declare(strict_types=1);

namespace Entropy\Router\Tests\Middleware;

use Entropy\Router\Middleware\MethodNotAllowedMiddleware;
use Entropy\Utils\HttpUtils\JsonResponse;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Pg\Router\RouteResult;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MethodNotAllowedMiddlewareTest extends TestCase
{
    private MethodNotAllowedMiddleware $middleware;
    private RequestHandlerInterface $handler;
    private ServerRequest $request;

    protected function setUp(): void
    {
        $this->middleware = new MethodNotAllowedMiddleware();
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->request = new ServerRequest('POST', new Uri('https://example.com'));
    }

    public function testPassesThroughWhenNoRouteResult(): void
    {
        $expectedResponse = new Response();

        $this->handler->expects($this->once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($expectedResponse);

        $response = $this->middleware->process($this->request, $this->handler);
        $this->assertSame($expectedResponse, $response);
    }

    public function testPassesThroughWhenNotMethodFailure(): void
    {
        $result = $this->createMock(RouteResult::class);
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

    public function testReturnsJsonResponseForJsonRequest(): void
    {
        $allowedMethods = ['GET', 'PUT'];
        $result = $this->createMock(RouteResult::class);
        $result->method('isMethodFailure')->willReturn(true);
        $result->method('getAllowedMethods')->willReturn($allowedMethods);

        $request = $this->request
            ->withAttribute(RouteResult::class, $result)
            ->withHeader('Accept', 'application/json');

        $response = $this->middleware->process($request, $this->handler);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED, $response->getStatusCode());
        $this->assertStringContainsString('GET,PUT', (string)$response->getBody());
    }

    public function testReturnsStandardResponseForNonJsonRequest(): void
    {
        $allowedMethods = ['GET', 'PUT'];
        $result = $this->createMock(RouteResult::class);
        $result->method('isMethodFailure')->willReturn(true);
        $result->method('getAllowedMethods')->willReturn($allowedMethods);

        $request = $this->request->withAttribute(RouteResult::class, $result);

        $response = $this->middleware->process($request, $this->handler);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals(StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED, $response->getStatusCode());
        $this->assertEquals('GET,PUT', $response->getHeader('Allow')[0]);
    }

    public function testJsonResponseForRequestWithJsonContent(): void
    {
        $allowedMethods = ['GET', 'PUT'];
        $result = $this->createMock(RouteResult::class);
        $result->method('isMethodFailure')->willReturn(true);
        $result->method('getAllowedMethods')->willReturn($allowedMethods);

        $request = $this->request
            ->withAttribute(RouteResult::class, $result)
            ->withHeader('Content-Type', 'application/json');

        $response = $this->middleware->process($request, $this->handler);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED, $response->getStatusCode());
        $this->assertStringContainsString('GET,PUT', (string)$response->getBody());
    }
}
