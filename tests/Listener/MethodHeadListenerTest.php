<?php

declare(strict_types=1);

namespace Entropy\Router\Tests\Listener;

use Entropy\Event\Events;
use Entropy\Event\RequestEvent;
use Entropy\Event\ResponseEvent;
use Entropy\Kernel\KernelInterface;
use Entropy\Router\Listener\MethodHeadListener;
use Fig\Http\Message\RequestMethodInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Uri;
use League\Event\ListenerPriority;
use Pg\Router\Route;
use Pg\Router\RouteResult;
use Pg\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class MethodHeadListenerTest extends TestCase
{
    private RouterInterface $router;
    private MethodHeadListener $listener;
    private KernelInterface $kernel;
    private ServerRequestInterface $request;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->kernel = $this->createMock(KernelInterface::class);
        $this->listener = new MethodHeadListener($this->router);
        $this->request = new ServerRequest('HEAD', new Uri('https://example.com'));
    }

    public function testIgnoreNonHeadRequest(): void
    {
        $request = new ServerRequest('GET', new Uri('https://example.com'));
        $event = new RequestEvent($this->kernel, $request);

        $this->listener->onRequest($event);

        $this->assertSame($request, $event->getRequest());
    }

    public function testIgnoreRequestWithoutRouteResult(): void
    {
        $event = new RequestEvent($this->kernel, $this->request);

        $this->listener->onRequest($event);

        $this->assertSame($this->request, $event->getRequest());
    }

    public function testIgnoreRequestWithMatchedRoute(): void
    {
        $result = $this->createMock(RouteResult::class);
        $route = $this->createMock(Route::class);
        $result->method('getMatchedRoute')->willReturn($route);

        $request = $this->request->withAttribute(RouteResult::class, $result);
        $event = new RequestEvent($this->kernel, $request);

        $this->listener->onRequest($event);

        $this->assertSame($request, $event->getRequest());
    }

    public function testForwardHeadToGetRequest(): void
    {
        $initialResult = $this->createMock(RouteResult::class);
        $initialResult->method('getMatchedRoute')->willReturn(null);

        $matchedResult = $this->createMock(RouteResult::class);
        $matchedResult->method('isFailure')->willReturn(false);
        $matchedResult->method('getMatchedAttributes')->willReturn(['id' => '1']);

        $this->router->expects($this->once())
            ->method('match')
            ->with($this->callback(function ($request) {
                return $request->getMethod() === RequestMethodInterface::METHOD_GET;
            }))
            ->willReturn($matchedResult);

        $request = $this->request->withAttribute(RouteResult::class, $initialResult);
        $event = new RequestEvent($this->kernel, $request);

        $this->listener->onRequest($event);

        $modifiedRequest = $event->getRequest();
        $this->assertEquals(RequestMethodInterface::METHOD_GET, $modifiedRequest->getMethod());
        $this->assertEquals('1', $modifiedRequest->getAttribute('id'));
        $this->assertEquals(
            RequestMethodInterface::METHOD_HEAD,
            $modifiedRequest->getAttribute(MethodHeadListener::FORWARDED_HTTP_METHOD_ATTRIBUTE)
        );
    }

    public function testEmptyResponseBodyForForwardedRequest(): void
    {
        $request = $this->request->withAttribute(
            MethodHeadListener::FORWARDED_HTTP_METHOD_ATTRIBUTE,
            RequestMethodInterface::METHOD_HEAD
        );

        $response = new Response(200, [], 'Original content');
        $event = new ResponseEvent($this->kernel, $request, $response);

        $this->listener->onResponse($event);

        $modifiedResponse = $event->getResponse();
        $this->assertInstanceOf(Stream::class, $modifiedResponse->getBody());
        $this->assertEquals('', (string) $modifiedResponse->getBody());
    }

    public function testPreserveResponseBodyForNonForwardedRequest(): void
    {
        $request = $this->request;
        $content = 'Original content';
        $response = new Response(200, [], $content);
        $event = new ResponseEvent($this->kernel, $request, $response);

        $this->listener->onResponse($event);

        $this->assertEquals($content, (string) $event->getResponse()->getBody());
    }

    public function testSubscribedEvents(): void
    {
        $events = MethodHeadListener::getSubscribedEvents();
        $this->assertArrayHasKey(Events::REQUEST, $events);
        $this->assertArrayHasKey(Events::RESPONSE, $events);
        $this->assertEquals(['onRequest', 800], $events[Events::REQUEST]);
        $this->assertEquals(['onResponse', ListenerPriority::LOW], $events[Events::RESPONSE]);
    }
}
