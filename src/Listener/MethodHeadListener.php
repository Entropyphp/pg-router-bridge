<?php

declare(strict_types=1);

namespace Entropy\Router\Listener;

use GuzzleHttp\Psr7\Utils;
use Pg\Router\RouteResult;
use Pg\Router\RouterInterface;
use Entropy\Event\RequestEvent;
use Entropy\Event\ResponseEvent;
use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use League\Event\ListenerPriority;
use Entropy\Event\Events;
use Entropy\Event\EventSubscriberInterface;

class MethodHeadListener implements EventSubscriberInterface
{
    public const FORWARDED_HTTP_METHOD_ATTRIBUTE = 'forwarded_http_method';

    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->getMethod() !== RequestMethod::METHOD_HEAD) {
            return;
        }

        $result = $request->getAttribute(RouteResult::class);
        if (!$result instanceof RouteResult) {
            return;
        }

        if ($result->getMatchedRoute()) {
            return;
        }

        $routeResult = $this->router->match($request->withMethod(RequestMethod::METHOD_GET));
        if ($routeResult->isFailure()) {
            return;
        }

        // Copy matched parameters like RouteMiddleware does
        foreach ($routeResult->getMatchedAttributes() as $param => $value) {
            $request = $request->withAttribute($param, $value);
        }

        $event->setRequest(
            $request
                ->withAttribute(RouteResult::class, $routeResult)
                ->withMethod(RequestMethod::METHOD_GET)
                ->withAttribute(
                    self::FORWARDED_HTTP_METHOD_ATTRIBUTE,
                    RequestMethod::METHOD_HEAD
                )
        );
    }

    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->getAttribute(self::FORWARDED_HTTP_METHOD_ATTRIBUTE)) {
            $response = $event->getResponse();

            $body = Utils::streamFor('');
            $event->setResponse($response->withBody($body));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::REQUEST => ['onRequest', 800],
            Events::RESPONSE => ['onResponse', ListenerPriority::LOW]
        ];
    }
}
