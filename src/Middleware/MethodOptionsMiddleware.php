<?php

declare(strict_types=1);

namespace Entropy\Router\Middleware;

use Pg\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use GuzzleHttp\Psr7\Response;

class MethodOptionsMiddleware implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        if ($request->getMethod() !== RequestMethod::METHOD_OPTIONS) {
            return $handler->handle($request);
        }

        $result = $request->getAttribute(RouteResult::class);
        if (! $result instanceof RouteResult) {
            return $handler->handle($request);
        }

        if ($result->isFailure() && ! $result->isMethodFailure()) {
            return $handler->handle($request);
        }

        if ($result->getMatchedRoute()) {
            return $handler->handle($request);
        }

        $allowedMethods = $result->getAllowedMethods();
        assert(is_array($allowedMethods));

        $origin = $request->getHeaderLine('origin');
        if (empty($origin)) {
            $origin = $request->getUri()->getScheme() . '://' . $request->getUri()->getHost() .
                ($request->getUri()->getPort() ? ':' . $request->getUri()->getPort() : '');
        }
        return new Response(200, [
            'Access-Control-Allow-Headers' =>
                'X-CSRF-TOKEN,' .
                'X-Requested-With,' .
                'Content-Type,' .
                'Origin,' .
                'Authorization,' .
                'Accept,' .
                'Client-Security-Token,' .
                'User-Agent',
            'Access-Control-Allow-Methods' => $allowedMethods,
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Credentials' => 'true',
            'Content-Type' => 'application/json,application/*+json;charset=UTF-8'
        ]);
    }
}
