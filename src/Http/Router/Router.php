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

use DI\Container;
use Rift\Contracts\Http\Router\RouterInterface;
use Rift\Contracts\Http\Request\RequestInterface;
use Rift\Contracts\Http\RoutesBox\RoutesBoxInterface;
use Rift\Core\Databus\Operation;
use Rift\Core\Databus\OperationOutcome;

class Router implements RouterInterface
{
    private array $compiledRoutes = [];
    private array $routes = [];
    
    public function __construct(
        private RoutesBoxInterface $routesBox, 
        private Container $container
    ) {
        $this->routes = $routesBox->getRoutes();
        $this->compileRoutes();
    }

    public function execute(RequestInterface $request): OperationOutcome
    {
        $path = $request->getPath();
        $method = strtoupper($request->getMethod());
        
        foreach ($this->compiledRoutes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            $params = $this->extractRouteParams($route['paramNames'], $matches);
            $payload = array_merge(
                $request->getQueryParams(),
                $params,
                $request->getBody()
            );

            // Middleware processing
            if (!empty($route['middlewares'])) {
                $middlewareResult = $this->processMiddlewares($route['middlewares'], $request);
                if (!$middlewareResult->isSuccess()) {
                    return $middlewareResult;
                }
                $request = $middlewareResult->result ?? $request;
            }

            // Route handler execution
            return $this->executeHandler($route['handler'], $payload);
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

    private function processMiddlewares(array $middlewares, RequestInterface $request): OperationOutcome
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

    private function executeHandler(string $handler, array $payload): OperationOutcome
    {
        if (empty($handler)) {
            return Operation::error(Operation::HTTP_INTERNAL_SERVER_ERROR, 'Path handler not found');
        }

        try {
            $handlerInstance = $this->container->get($handler);
            return $handlerInstance->execute($payload);
        } catch (\Throwable $e) {
            return Operation::error(
                Operation::HTTP_INTERNAL_SERVER_ERROR, 
                "Invalid path handler: {$e->getMessage()}",
                ['debug' => ['trace' => $e->getTraceAsString()]]
            );
        }
    }
}