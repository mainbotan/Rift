<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * RESTful router.
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Core\Http\Router;

use Psr\Container\ContainerInterface;
use Rift\Contracts\Http\Router\RouterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rift\Contracts\Http\RoutesBox\RoutesBoxInterface;
use Rift\Core\Databus\Result;
use Rift\Core\Databus\ResultType;

/**
 * @package Rift\Core\Http\Router
 * @version 1.0.0
 * @author mainbotan
 * @license MIT
 */
class Router implements RouterInterface
{
    /** @var array<array{
     * method: string, 
     * path: string, 
     * regex: string, 
     * paramNames: string[], 
     * middlewares: string[], 
     * handler: string
     * limit: []
     * }> */
    private array $compiledRoutes = [];

    private array $routes = [];
    
    public function __construct(
        private RoutesBoxInterface $routesBox, 
        private ContainerInterface $container
    ) {
        $this->routes = $routesBox->getRoutes();
        $this->compileRoutes();
    }
    
    /**
     * @param ServerRequestInterface $request
     * @return ResultType 
     */
    public function execute(ServerRequestInterface $request): ResultType
    {
        $path = $request->getUri()->getPath();
        $method = strtoupper($request->getMethod());
        
        foreach ($this->compiledRoutes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            // Извлекаем параметры из URL (например, `uid` из `/clients/{uid}`)
            $params = [];
            foreach ($route['paramNames'] as $name) {
                if (isset($matches[$name])) {
                    $params[$name] = $matches[$name];
                }
            }

            // Добавляем параметры в $request
            $request = $request
                ->withAttribute('route', $route)
                ->withAttribute('params', $params); // <-- Новый атрибут

            // Middleware processing
            if (!empty($route['middlewares'])) {
                $middlewareResult = $this->processMiddlewares($route['middlewares'], $request);
                if (!$middlewareResult->isSuccess()) {
                    return $middlewareResult;
                }
                $request = $middlewareResult->result ?? $request;
            }

            // Route handler execution
            return $this->dispatchToHandler($route['handler'], $request);
        }

        return Result::Failure(Result::HTTP_NOT_FOUND, 'Path not found');
    }

    /**
     * @return void
     */
    private function compileRoutes(): void
    {
        foreach ($this->routes as $route) {
            [$regex, $paramNames] = $this->compileRoutePattern($route['path']);
            
            $this->compiledRoutes[] = [
                'method' => strtoupper($route['method']),
                'path' => $route['path'],
                'regex' => $regex,
                'paramNames' => $paramNames,
                'middlewares' => $route['middlewares'] ?? [],
                'handler' => $route['handler'],
                'limit' => $route['limit'] ?? []
            ];
        }
    }

    /**
     * @param string $route
     * @return array
     */
    private function compileRoutePattern(string $route): array
    {
        $paramNames = [];
        $regex = preg_replace_callback(
            '/{(\w+)}/',
            function ($matches) use (&$paramNames) {
                $paramNames[] = $matches[1];
                return '(?P<'.$matches[1].'>[^/]+)';
            },
            $route
        );

        return ['#^'.$regex.'$#', $paramNames];
    }

    /**
     * Middlewares chain processing
     * @param array @middlewares
     * @param ServerRequestInterface $request
     * @return ResultType @result
     */
    private function processMiddlewares(array $middlewares, ServerRequestInterface $request): ResultType
    {
        foreach ($middlewares as $middleware) {
            if (!class_exists($middleware)) {
                return Result::Failure(Result::HTTP_INTERNAL_SERVER_ERROR, "Middleware class {$middleware} not found");
            }
            
            $result = $this->container->get($middleware)->execute($request);
            if (!$result->isSuccess()) {
                return $result;
            }
            
            $request = $result->result ?? $request;
        }
        
        return Result::Success($request);
    }

    /**
     * The handler instance is taken from
     * the di-container passed when the router
     * is declared.
     * 
     * @param string $handler - Link to handler class
     * @param ServerRequestInterface $request
     * @return ResultType
     */
    private function dispatchToHandler(string $handler, ServerRequestInterface $request): ResultType
    {
        if (empty($handler)) {
            return Result::Failure(Result::HTTP_INTERNAL_SERVER_ERROR, 'Path handler not found');
        }

        try {
            $handlerInstance = $this->container->get($handler);
            return $handlerInstance->execute($request);
        } catch (\Throwable $e) {
            return Result::Failure(
                Result::HTTP_INTERNAL_SERVER_ERROR, 
                "Invalid path handler: {$e->getMessage()}",
                ['debug' => ['trace' => $e->getTraceAsString()]]
            );
        }
    }
}