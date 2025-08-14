<?php

declare(strict_types=1);

namespace Entropy\Router\Listener;

use Entropy\Utils\HttpUtils\JsonResponse;
use GuzzleHttp\Psr7\Response;
use Pg\Router\RouteResult;
use Entropy\Event\RequestEvent;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Entropy\Event\Events;
use Entropy\Event\EventSubscriberInterface;
use Entropy\Utils\HttpUtils\RequestUtils;

class MethodNotAllowedListener implements EventSubscriberInterface
{
    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        /** @var RouteResult $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);

        if (null === $routeResult) {
            return;
        }

        if ($routeResult->isMethodFailure()) {
            if (RequestUtils::isJson($event->getRequest()) || RequestUtils::wantJson($event->getRequest())) {
                $event->setResponse(
                    new JsonResponse(
                        statusCode::STATUS_METHOD_NOT_ALLOWED,
                        json_encode(
                            "Method not Allowed. Allowed methods: " .
                            implode(',', $routeResult->getAllowedMethods())
                        )
                    )
                );
                return;
            }
            $event->setResponse((new Response())
                ->withStatus(StatusCode::STATUS_METHOD_NOT_ALLOWED)
                ->withHeader('Allow', implode(',', $routeResult->getAllowedMethods())));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::REQUEST => 600
        ];
    }
}
