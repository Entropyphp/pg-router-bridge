<?php

declare(strict_types=1);

namespace Entropy\Router\Tests\Listener;

use Entropy\Event\Events;
use Entropy\Event\RequestEvent;
use Entropy\Kernel\KernelInterface;
use Entropy\Router\Listener\MethodOptionsListener;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Pg\Router\Route;
use Pg\Router\RouteResult;
use PHPUnit\Framework\TestCase;

class MethodOptionsListenerTest extends TestCase
{
    private MethodOptionsListener $listener;
    private KernelInterface $kernel;
    private ServerRequest $request;

    protected function setUp(): void
    {
        $this->listener = new MethodOptionsListener();
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->request = new ServerRequest('OPTIONS', new Uri('https://example.com'));
    }

    public function testIgnoreNonOptionsRequest(): void
    {
        $request = new ServerRequest('GET', new Uri('https://example.com'));
        $event = new RequestEvent($this->kernel, $request);

        ($this->listener)($event);

        $this->assertNull($event->getResponse());
    }

    public function testIgnoreOptionsRequestWithoutRouteResult(): void
    {
        $event = new RequestEvent($this->kernel, $this->request);

        ($this->listener)($event);

        $this->assertNull($event->getResponse());
    }

    public function testIgnoreOptionsRequestWithMatchedRoute(): void
    {
        $result = $this->createMock(RouteResult::class);
        $route = $this->createMock(Route::class);
        $result->method('getMatchedRoute')->willReturn($route);

        $request = $this->request->withAttribute(RouteResult::class, $result);
        $event = new RequestEvent($this->kernel, $request);

        ($this->listener)($event);

        $this->assertNull($event->getResponse());
    }

    public function testIgnoreOptionsRequestWithNonMethodFailure(): void
    {
        $result = $this->createMock(RouteResult::class);
        $result->method('isFailure')->willReturn(true);
        $result->method('isMethodFailure')->willReturn(false);
        $result->method('getMatchedRoute')->willReturn(null);

        $request = $this->request->withAttribute(RouteResult::class, $result);
        $event = new RequestEvent($this->kernel, $request);

        ($this->listener)($event);

        $this->assertNull($event->getResponse());
    }

    public function testHandleOptionsRequestWithMethodFailure(): void
    {
        $allowedMethods = ['GET', 'POST'];
        $result = $this->createMock(RouteResult::class);
        $result->method('isFailure')->willReturn(true);
        $result->method('isMethodFailure')->willReturn(true);
        $result->method('getMatchedRoute')->willReturn(null);
        $result->method('getAllowedMethods')->willReturn($allowedMethods);

        $request = $this->request->withAttribute(RouteResult::class, $result);
        $event = new RequestEvent($this->kernel, $request);

        ($this->listener)($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($allowedMethods, $response->getHeader('Access-Control-Allow-Methods'));
        $this->assertEquals('https://example.com', $response->getHeader('Access-Control-Allow-Origin')[0]);
        $this->assertEquals('true', $response->getHeader('Access-Control-Allow-Credentials')[0]);
        $this->assertStringContainsString('application/json', $response->getHeader('Content-Type')[0]);
    }

    public function testHandleOptionsRequestWithCustomOrigin(): void
    {
        $origin = 'https://custom-origin.com';
        $allowedMethods = ['GET', 'POST'];
        $result = $this->createMock(RouteResult::class);
        $result->method('isFailure')->willReturn(true);
        $result->method('isMethodFailure')->willReturn(true);
        $result->method('getMatchedRoute')->willReturn(null);
        $result->method('getAllowedMethods')->willReturn($allowedMethods);

        $request = $this->request
            ->withAttribute(RouteResult::class, $result)
            ->withHeader('Origin', $origin);
        $event = new RequestEvent($this->kernel, $request);

        ($this->listener)($event);

        $response = $event->getResponse();
        $this->assertEquals($origin, $response->getHeader('Access-Control-Allow-Origin')[0]);
    }

    public function testSubscribedEvents(): void
    {
        $events = MethodOptionsListener::getSubscribedEvents();
        $this->assertArrayHasKey(Events::REQUEST, $events);
        $this->assertEquals(700, $events[Events::REQUEST]);
    }
}
