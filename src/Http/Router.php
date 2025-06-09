<?php

/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Processing an incoming Request object, 
 * obtaining a path based on the path configuration, 
 * calling middleware, and requesting a target route handler.
 * |
 * |--------------------------------------------------------------------------
 */

namespace Rift\Core\Http;

use Exception;
use Rift\Core\Containers\DI;
use Rift\Core\Http\Request;
use Rift\Core\Http\RoutesBox;
use Rift\Core\Contracts\Operation;
use Rift\Core\Contracts\OperationOutcome;

class Router extends Operation {
    public function __construct(
        private array $routes, private DI $DI
    ){ }
    public static function fromRoutesBox(RoutesBox $routesBox, DI $DI) {
        return new static($routesBox->getRoutes(), $DI);
    }

    /**
     * Обработка входящего запроса
     */
    public function execute(Request $request): OperationOutcome {
        $path = $request->getPath();
        $method = $request->getMethod();
        $getParams = $request->getQueryParams();
        $data = $request->getBody();

        foreach ($this->routes as $route) {
            if (strtoupper($route['method']) !== strtoupper($method)) {
                continue;
            }

            [$regex, $paramNames] = $this->parseRoute($route['path']);


            if (preg_match($regex, $path, $matches)) {
                $pathParams = [];

                foreach ($paramNames as $name) {
                    $pathParams[$name] = $matches[$name] ?? null;
                }

                // Объединяем параметры пути и query
                $payloadData = array_merge($getParams, $pathParams, $data);
                
                $routeMiddlewares = $route['middlewares'];
                if (!empty($routeMiddlewares)) {
                    $middlewaresResult = $this->processMiddlewares($routeMiddlewares, $request);
                    if (!$middlewaresResult->isSuccess()) {
                        return $middlewaresResult;
                    }
                }

                $routeHandler = $route['handler'];
                if (empty($routeHandler)) {
                    return self::error(self::HTTP_INTERNAL_SERVER_ERROR, 'Path handler not found');
                }
                
                $handler = new $routeHandler($this->DI); // DI injection
                try {
                    return $handler->execute($payloadData);
                } catch (Exception $e) {
                    return self::error(self::HTTP_INTERNAL_SERVER_ERROR, "Invalid path handler: {$e->getMessage()}");
                }
            }
        }
        return parent::response(
            null,
            self::HTTP_NOT_FOUND,
            'Path not found'
        );
    }

    /**
     * Обработка мидлваров
     */
    private function processMiddlewares(array $middlewares, Request $request): OperationOutcome
    {
        foreach ($middlewares as $middleware) {
            if (!class_exists($middleware)) {
                return self::error(500, "Middleware class {$middleware} not found");
            }
            
            $result = (new $middleware)->execute($request);
            if (!$result->isSuccess()) {
                return $result;
            }
            
            $request = $result->result ?? $request;
        }
        
        return self::success($request);
    }

    /**
     * Преобразует шаблон маршрута в регулярное выражение
     */
    private function parseRoute(string $route): array
    {
        $paramNames = [];
        $regex = preg_replace_callback('/{(\w+)}/', function ($matches) use (&$paramNames) {
            $paramNames[] = $matches[1];
            return '(?P<' . $matches[1] . '>[^/]+)';
        }, $route);

        return ['#^' . $regex . '$#', $paramNames];
    }
}