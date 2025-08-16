<?php

/**
 * @see       https://github.com/mezzio/mezzio-router for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-router/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-router/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Entropy\Router\Middleware;

use Entropy\Utils\HttpUtils\RequestUtils;
use Entropy\Utils\HttpUtils\JsonResponse;
use Psr\Http\Server\MiddlewareInterface;
use GuzzleHttp\Psr7\Response;
use Pg\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Fig\Http\Message\StatusCodeInterface as StatusCode;

use function implode;

/**
 * Emit a 405-Method Not Allowed response
 *
 * If the request composes a route result, and the route result represents a
 * failure due to the request method, this middleware will emit a 405 response,
 * along with an Allow header indicating allowed methods, as reported by the
 * route result.
 *
 * If no route result is composed, and/or it's not the result of a method
 * failure, it passes handling to the provided handler.
 */
class MethodNotAllowedMiddleware implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeResult = $request->getAttribute(RouteResult::class);
        if (!$routeResult || !$routeResult->isMethodFailure()) {
            return $handler->handle($request);
        }

        if (RequestUtils::isJson($request) || RequestUtils::wantJson($request)) {
            return new JsonResponse(
                statusCode::STATUS_METHOD_NOT_ALLOWED,
                json_encode(
                    "Method not Allowed. Allowed methods: " .
                    implode(',', $routeResult->getAllowedMethods())
                )
            );
        }

        return (new Response())
            ->withStatus(StatusCode::STATUS_METHOD_NOT_ALLOWED)
            ->withHeader('Allow', implode(',', $routeResult->getAllowedMethods()));
    }
}
