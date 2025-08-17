<?php

declare(strict_types=1);

namespace Entropy\Router\Attribute;

use Attribute;
use Entropy\Router\Attribute\Exception\RouteAttributeException;

use function is_null;
use function is_string;

/**
 *
 * Ex: #[Route("/route/{id:\d+}", name:"path.route", methods:["GET"], middlewares:[loginMiddleware::class])]
 *
 *
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION | Attribute::IS_REPEATABLE)]
class Route
{
    private mixed $parameters;
    private ?string $path;
    private ?string $name;
    private ?string $host;
    private ?string $port;
    private ?array $methods;
    private ?array $schemes;
    private array $middlewares;

    /**
     * @param array $parameters
     * @param string|null $path
     * @param string|null $name
     * @param string|null $host
     * @param string|null $port
     * @param array $methods
     * @param array $schemes
     * @param array $middlewares
     * @throws RouteAttributeException
     */
    public function __construct(
        array $parameters = [],
        ?string $path = null,
        ?string $name = null,
        ?string $host = null,
        ?string $port = null,
        array $methods = [],
        array $schemes = [],
        array $middlewares = []
    ) {
        $this->parameters = $parameters;

        $this->path = $parameters['value'] ?? (is_string($parameters) ? $parameters : $path);
        $this->name = $parameters['name'] ?? (!is_null($name) ? $name : null);
        $this->host = $parameters['host'] ?? (!is_null($host) ? $host : null);
        $this->port = $parameters['port'] ?? (!is_null($port) ? $port : null);
        $this->methods = $parameters['methods'] ?? ([] !== $methods ? $methods : null);
        $this->schemes = $parameters['schemes'] ?? ([] !== $schemes ? $schemes : null);
        $this->middlewares = $middlewares;

        // Check if the path is defined
        if (null === $this->path) {
            throw new RouteAttributeException(
                '#[Route("/route/{id:\d+}", name:"path.route",
                methods:["GET"]]) expects first parameter "path", null given.'
            );
        }
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Get the value of the route path
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Get the value of the route name
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get the value of host
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * Get the value of methods
     */
    public function getMethods(): ?array
    {
        return $this->methods;
    }

    /**
     * Get the value of schemes
     */
    public function getSchemes(): ?array
    {
        return $this->schemes;
    }

    /**
     * Get the middlewares value
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getPort(): ?string
    {
        return $this->port;
    }

    public function setPort(string $port): void
    {
        $this->port = $port;
    }
}
