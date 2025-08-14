<?php

declare(strict_types=1);

namespace Entropy\Router\Listener;

use GuzzleHttp\Psr7\Response;
use Pg\Router\RouteResult;
use Entropy\Event\RequestEvent;
use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Entropy\Event\Events;
use Entropy\Event\EventSubscriberInterface;

class MethodOptionsListener implements EventSubscriberInterface
{
    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->getMethod() !== RequestMethod::METHOD_OPTIONS) {
            return;
        }

        $result = $request->getAttribute(RouteResult::class);
        if (! $result instanceof RouteResult) {
            return;
        }

        if ($result->isFailure() && ! $result->isMethodFailure()) {
            return;
        }

        if ($result->getMatchedRoute()) {
            return;
        }

        $allowedMethods = $result->getAllowedMethods();
        assert(is_array($allowedMethods));

        $origin = $request->getHeaderLine('origin');
        if (empty($origin)) {
            $origin = $request->getUri()->getScheme() . '://' . $request->getUri()->getHost() .
                ($request->getUri()->getPort() ? ':' . $request->getUri()->getPort() : '');
        }
        $event->setResponse(new Response(200, [
            'Access-Control-Allow-Headers' =>
                'X-CSRF-TOKEN,' .
                'X-Requested-With,' .
                'Content-Type,' .
                'Origin,' .
                'Authorization,' .
                'Accept,' .
                'Client-Security-Token,' .
                'User-Agent',
            'Access-Control-Allow-Methods' => $result->getAllowedMethods(),
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Credentials' => 'true',
            'Content-Type' => 'application/json,application/*+json;charset=UTF-8'
        ]));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::REQUEST => 700
        ];
    }
}
