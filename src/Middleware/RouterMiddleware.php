<?php

declare(strict_types=1);

namespace Entropy\Router\Middleware;

use GuzzleHttp\Psr7\Response;
use Pg\Router\Route;
use Pg\Router\RouteResult;
use pg\Router\RouterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouterMiddleware implements MiddlewareInterface
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Redirect if trailing slash on url
        $response = $this->trailingSlash($request);
        if (null !== $response) {
            return $response;
        }

        // Check http method
        $request = $this->method($request);

        $result = $this->router->match($request);

        if ($result->isMethodFailure()) {
            $request = $request->withAttribute(get_class($result), $result);
            return $handler->handle($request);
        }

        if ($result->isFailure()) {
            return $handler->handle($request);
        }

        /** @var Route $route */
        $route = $result->getMatchedRoute();
        $params = $result->getMatchedAttributes();
        $request = array_reduce(
            array_keys($params),
            function ($request, $key) use ($params) {
                /** @var ServerRequestInterface $request */
                return $request->withAttribute($key, $params[$key]);
            },
            $request
        );
        $request = $request->withAttribute(get_class($result), $result)
        ->withAttribute('_controller', $route->getCallback())
        ->withAttribute('_params', $params);

        /** @var ServerRequestInterface $request */
        $request = $request->withAttribute(get_class($result), $result);
        return $handler->handle($request);
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
}
