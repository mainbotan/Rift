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
use Rift\Core\Databus\Operation;
use Rift\Core\Databus\OperationOutcome;

class Router implements RouterInterface
{
    private array $compiledRoutes = [];
    private array $routes = [];
    
    public function __construct(
        private RoutesBoxInterface $routesBox, 
        private ContainerInterface $container
    ) {
        $this->routes = $routesBox->getRoutes();
        $this->compileRoutes();
    }

    public function execute(ServerRequestInterface $request): OperationOutcome
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

            // Middleware processing
            if (!empty($route['middlewares'])) {
                $middlewareResult = $this->processMiddlewares($route['middlewares'], $request);
                if (!$middlewareResult->isSuccess()) {
                    return $middlewareResult;
                }
                $request = $middlewareResult->result ?? $request;
            }

            // Route handler execution
            return $this->executeHandler($route['handler'], $request);
        }

        return Operation::error(Operation::HTTP_NOT_FOUND, 'Path not found');
    }

    private function compileRoutes(): void
    {
        foreach ($this->routes as $route) {
            [$regex, $paramNames] = $this->parseRoute($route['path']);
            
            $this->compiledRoutes[] = [
                'method' => strtoupper($route['method']),
                'path' => $route['path'],
                'regex' => $regex,
                'paramNames' => $paramNames,
                'middlewares' => $route['middlewares'] ?? [],
                'handler' => $route['handler']
            ];
        }
    }

    private function parseRoute(string $route): array
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

    private function extractRouteParams(array $paramNames, array $matches): array
    {
        $params = [];
        foreach ($paramNames as $name) {
            $params[$name] = $matches[$name] ?? null;
        }
        return $params;
    }

    private function processMiddlewares(array $middlewares, ServerRequestInterface $request): OperationOutcome
    {
        foreach ($middlewares as $middleware) {
            if (!class_exists($middleware)) {
                return Operation::error(Operation::HTTP_INTERNAL_SERVER_ERROR, "Middleware class {$middleware} not found");
            }
            
            $result = $this->container->get($middleware)->execute($request);
            if (!$result->isSuccess()) {
                return $result;
            }
            
            $request = $result->result ?? $request;
        }
        
        return Operation::success($request);
    }

    private function executeHandler(string $handler, ServerRequestInterface $request): OperationOutcome
    {
        if (empty($handler)) {
            return Operation::error(Operation::HTTP_INTERNAL_SERVER_ERROR, 'Path handler not found');
        }

        try {
            $handlerInstance = $this->container->get($handler);
            return $handlerInstance->execute($request);
        } catch (\Throwable $e) {
            return Operation::error(
                Operation::HTTP_INTERNAL_SERVER_ERROR, 
                "Invalid path handler: {$e->getMessage()}",
                ['debug' => ['trace' => $e->getTraceAsString()]]
            );
        }
    }
}