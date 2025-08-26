<?php

declare(strict_types=1);

namespace Entropy\Router\Tests\Listener;

use Entropy\Event\Events;
use Entropy\Event\RequestEvent;
use Entropy\Kernel\KernelInterface;
use Entropy\Router\Listener\MethodNotAllowedListener;
use Entropy\Utils\HttpUtils\JsonResponse;
use Fig\Http\Message\StatusCodeInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Pg\Router\RouteResult;
use PHPUnit\Framework\TestCase;

class MethodNotAllowedListenerTest extends TestCase
{
    private MethodNotAllowedListener $listener;
    private KernelInterface $kernel;
    private ServerRequest $request;

    protected function setUp(): void
    {
        $this->listener = new MethodNotAllowedListener();
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->request = new ServerRequest('POST', new Uri('https://example.com'));
    }

    public function testIgnoreRequestWithoutRouteResult(): void
    {
        $event = new RequestEvent($this->kernel, $this->request);

        ($this->listener)($event);

        $this->assertNull($event->getResponse());
    }

    public function testIgnoreNonMethodFailureRequest(): void
    {
        $result = $this->createMock(RouteResult::class);
        $result->method('isMethodFailure')->willReturn(false);

        $request = $this->request->withAttribute(RouteResult::class, $result);
        $event = new RequestEvent($this->kernel, $request);

        ($this->listener)($event);

        $this->assertNull($event->getResponse());
    }

    public function testHandleMethodFailureWithJsonRequest(): void
    {
        $allowedMethods = ['GET', 'PUT'];
        $result = $this->createMock(RouteResult::class);
        $result->method('isMethodFailure')->willReturn(true);
        $result->method('getAllowedMethods')->willReturn($allowedMethods);

        $request = $this->request
            ->withAttribute(RouteResult::class, $result)
            ->withHeader('Accept', 'application/json');
        $event = new RequestEvent($this->kernel, $request);

        ($this->listener)($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED, $response->getStatusCode());
        $this->assertStringContainsString('GET,PUT', (string)$response->getBody());
    }

    public function testHandleMethodFailureWithJsonContent(): void
    {
        $allowedMethods = ['GET', 'PUT'];
        $result = $this->createMock(RouteResult::class);
        $result->method('isMethodFailure')->willReturn(true);
        $result->method('getAllowedMethods')->willReturn($allowedMethods);

        $request = $this->request
            ->withAttribute(RouteResult::class, $result)
            ->withHeader('Content-Type', 'application/json');
        $event = new RequestEvent($this->kernel, $request);

        ($this->listener)($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED, $response->getStatusCode());
        $this->assertStringContainsString('GET,PUT', (string)$response->getBody());
    }

    public function testHandleMethodFailureWithStandardRequest(): void
    {
        $allowedMethods = ['GET', 'PUT'];
        $result = $this->createMock(RouteResult::class);
        $result->method('isMethodFailure')->willReturn(true);
        $result->method('getAllowedMethods')->willReturn($allowedMethods);

        $request = $this->request->withAttribute(RouteResult::class, $result);
        $event = new RequestEvent($this->kernel, $request);

        ($this->listener)($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(StatusCodeInterface::STATUS_METHOD_NOT_ALLOWED, $response->getStatusCode());
        $this->assertEquals('GET,PUT', $response->getHeader('Allow')[0]);
    }

    public function testSubscribedEvents(): void
    {
        $events = MethodNotAllowedListener::getSubscribedEvents();
        $this->assertArrayHasKey(Events::REQUEST, $events);
        $this->assertEquals(600, $events[Events::REQUEST]);
    }
}
