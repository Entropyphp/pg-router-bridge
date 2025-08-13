<?php

declare(strict_types=1);

namespace Entropy\Router\Listener;

use Entropy\Event\EventSubscriberInterface;
use Entropy\Event\RequestEvent;
use Entropy\Router\Exception\PageNotFoundException;
use GuzzleHttp\Psr7\Response;
use Pg\Router\Route;
use Pg\Router\RouteResult;
use Pg\Router\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouterListener implements EventSubscriberInterface
{
    /**
     * @var RouterInterface
     */
    private RouterInterface $router;

    /**
     * RouterListener constructor.
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @throws PageNotFoundException
     */
    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Redirect if trailing slash on url
        $response = $this->trailingSlash($request);
        if (null !== $response) {
            $event->setResponse($response);
            return;
        }

        // Check http method
        $request = $this->method($request);

        $result = $this->router->match($request);

        if ($result->isMethodFailure()) {
            $event->setRequest($request->withAttribute(get_class($result), $result));
            return;
        }

        if ($result->isFailure()) {
            $event->setRequest($request);
            throw new PageNotFoundException();
        }

        /** @var Route $route */
        $route = $result->getMatchedRoute();
        $params = $result->getMatchedAttributes();
        $request = array_reduce(
            array_keys($params),
            function ($request, $key) use ($params) {
                return $request->withAttribute($key, $params[$key]);
            },
            $request
        );
        $event->setRequest($request->withAttribute(get_class($result), $result)
            ->withAttribute('_controller', $route->getCallback())
            ->withAttribute('_params', $result->getMatchedAttributes()));
    }

    private function trailingSlash(ServerRequestInterface $request): ?ResponseInterface
    {
        $uri = $request->getUri()->getPath();
        if (!empty($uri) && $uri !== '/' && $uri[strlen($uri) - 1] === '/') {
            return (new Response())
                ->withStatus(301)
                ->withHeader('Location', substr($uri, 0, -1));
        }
        return null;
    }

    private function method(ServerRequestInterface $request): ServerRequestInterface
    {
        $parseBody = $request->getParsedBody();
        if (
            is_array($parseBody) &&
            array_key_exists('_method', $parseBody) &&
            in_array($parseBody['_method'], ['DELETE', 'PUT', 'PATCH'])
        ) {
            $request = $request->withMethod($parseBody['_method']);
        }
        return $request;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RouteResult $result
     * @param ResponseInterface|null $response
     * @return ResponseInterface|null
     */
    private function redirectHttps(
        ServerRequestInterface $request,
        RouteResult $result,
        ?ResponseInterface $response
    ): ?ResponseInterface {
        $scheme = $request->getUri()->getScheme();
        /** @var Route $route */
        $route = $result->getMatchedRoute();
        if (! $route->allowsScheme($scheme)) {
            $uriClass = $request->getUri();
            $newScheme = in_array('https', $route->getSchemes(), true)
                ? 'https'
                : $route->getSchemes()[0];
            $path = $response ? $response->getHeaderLine('Location') : $uriClass->getPath();
            $uri = $uriClass
                ->withScheme($newScheme)
                ->withPath($path)
                ->__toString();
            $response = $response ?
                $response->withHeader('Location', $uri) :
                (new Response())
                    ->withStatus(301)
                    ->withHeader('Location', $uri);
        }
        return $response;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::REQUEST => 900
        ];
    }
}
