<?php

namespace Entropy\Router\Loader;

use Entropy\Utils\Attribute\AttributeLoader;
use Entropy\Router\Attribute\Route as AnnotRoute;
use Pg\Router\Route;
use Pg\Router\RouterInterface;
use ReflectionClass;
use ReflectionMethod;

class RouteLoader
{
    protected RouterInterface $router;
    protected AttributeLoader $attributeLoader;

    public function __construct(
        RouterInterface $collector,
        AttributeLoader $attributeLoader
    ) {
        $this->router = $collector;
        $this->attributeLoader = $attributeLoader;
    }

    /**
     * Parse annotations @Route, add routes to the router
     *
     * @param string $className
     * @return Route|null
     */
    public function load(string $className): ?array
    {
        if (!class_exists($className)) {
            return null;
        }

        $reflectionClass = new ReflectionClass($className);
        if ($reflectionClass->isAbstract()) {
            return null;
        }

        $classAttribute = $this->attributeLoader
            ->getClassAttribute($reflectionClass, AnnotRoute::class);

        $routes = [];
        foreach ($reflectionClass->getMethods() as $method) {
            /** @var AnnotRoute $methodAttribute*/
            foreach (
                $this->attributeLoader->getMethodAttributes($method, AnnotRoute::class) as $methodAttribute
            ) {
                $routes[] = $this->addRoute($methodAttribute, $method, $classAttribute);
            }
        }

        if (empty($routes) && $classAttribute && $reflectionClass->hasMethod('__invoke')) {
            foreach (
                $this->attributeLoader->getClassAttributes($reflectionClass, AnnotRoute::class) as $classAttribute
            ) {
                /** @var Route $route */
                $routes[] = $this->router->route(
                    $classAttribute->getPath(),
                    $reflectionClass->getName(),
                    $classAttribute->getName(),
                    $classAttribute->getMethods()
                )
                    ->setSchemes($classAttribute->getSchemes())
                    ->setHost($classAttribute->getHost())
                    ->setPort($classAttribute->getPort())
                    ->middlewares($classAttribute->getMiddlewares());
            }
        }

        gc_mem_caches();

        if (empty($routes)) {
            return null;
        }

        return $routes;
    }

    /**
     * Add a route to router
     *
     * @param AnnotRoute $methodAnnotation
     * @param ReflectionMethod $method
     * @param AnnotRoute|null $classAnnotation
     * @return Route
     */
    protected function addRoute(
        AnnotRoute $methodAnnotation,
        ReflectionMethod $method,
        ?AnnotRoute $classAnnotation
    ): Route {

        $path = $methodAnnotation->getPath();
        if ($classAnnotation) {
            $path = $classAnnotation->getPath() . $path;
        }
        return $this->router->route(
            $path,
            $method->getDeclaringClass()->getName() . "::" . $method->getName(),
            $methodAnnotation->getName(),
            $methodAnnotation->getMethods()
        )
            ->setSchemes($methodAnnotation->getSchemes())
            ->setHost($methodAnnotation->getHost())
            ->setPort($methodAnnotation->getPort())
            ->middlewares($methodAnnotation->getMiddlewares());
    }
}
