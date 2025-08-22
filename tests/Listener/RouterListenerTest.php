<?php

declare(strict_types=1);

namespace Entropy\Router\Tests\Listener;

use Entropy\Event\RequestEvent;
use Entropy\Kernel\KernelInterface;
use Entropy\Router\Exception\PageNotFoundException;
use Entropy\Router\Listener\RouterListener;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Pg\Router\Route;
use Pg\Router\RouteResult;
use Pg\Router\RouterInterface;
use PHPUnit\Framework\TestCase;

class RouterListenerTest extends TestCase
{
    private KernelInterface $kernelEvent;
    private RouterInterface $router;
    private RouterListener $routerListener;
    private RequestEvent $event;

    protected function setUp(): void
    {
        $this->kernelEvent = $this->createMock(KernelInterface::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->routerListener = new RouterListener($this->router);
        $request = new ServerRequest('GET', new Uri('https://example.com'));
        $this->event = new RequestEvent($this->kernelEvent, $request);
    }

    /**
     * @throws PageNotFoundException
     */
    public function testTrailingSlashRedirect(): void
    {
        $request = new ServerRequest('GET', new Uri('https://example.com/test/'));
        $event = new RequestEvent($this->kernelEvent, $request);

        $this->routerListener->__invoke($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals('/test', $response->getHeaderLine('Location'));
    }

    /**
     * @throws PageNotFoundException
     */
    public function testMethodOverride(): void
    {
        $request = (new ServerRequest(
            'POST',
            new Uri('https://example.com')
        ))->withParsedBody(['_method' => 'DELETE']);
        $event = new RequestEvent($this->kernelEvent, $request);

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

        $this->routerListener->__invoke($event);
    }

    public function testRouteNotFound(): void
    {
        $result = $this->createMock(RouteResult::class);
        $result->method('isMethodFailure')->willReturn(false);
        $result->method('isFailure')->willReturn(true);

        $this->router->method('match')->willReturn($result);

        $this->expectException(PageNotFoundException::class);
        $this->routerListener->__invoke($this->event);
    }

    /**
     * @throws PageNotFoundException
     */
    public function testMethodNotAllowed(): void
    {
        $result = $this->createMock(RouteResult::class);
        $result->method('isMethodFailure')->willReturn(true);

        $this->router->method('match')->willReturn($result);

        $this->routerListener->__invoke($this->event);

        $request = $this->event->getRequest();
        $this->assertInstanceOf(RouteResult::class, $request->getAttribute(get_class($result)));
    }

    /**
     * @throws PageNotFoundException
     */
    public function testSuccessfulRouteMatch(): void
    {
        $result = $this->createMock(RouteResult::class);
        $result->method('isMethodFailure')->willReturn(false);
        $result->method('isFailure')->willReturn(false);

        $route = $this->createMock(Route::class);
        $callback = function () {
            return new Response();
        };
        $route->method('getCallback')->willReturn($callback);

        $result->method('getMatchedRoute')->willReturn($route);
        $result->method('getMatchedAttributes')->willReturn(['id' => '1']);

        $this->router->method('match')->willReturn($result);

        $this->routerListener->__invoke($this->event);

        $request = $this->event->getRequest();
        $this->assertEquals($callback, $request->getAttribute('_controller'));
        $this->assertEquals(['id' => '1'], $request->getAttribute('_params'));
        $this->assertEquals('1', $request->getAttribute('id'));
    }
}
