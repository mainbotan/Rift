<?php

namespace Rift\Core\Http;

use Rift\Core\Contracts\Operation;
use Rift\Core\Contracts\OperationOutcome;

class RoutesBox extends Operation {
    const POST_METHOD = 'POST';
    const GET_METHOD = 'GET';
    const PUT_METHOD = 'PUT';
    const PATCH_METHOD = 'PATCH';
    const DELETE_METHOD = 'DELETE';

    public array $routes = [];

    public function getRoutes(): array {
        return $this->routes;
    }

    protected function addRoute(
        string $method, string $path, string $handler, array $middlewares
    ) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    } 
    public function group(string $prefix, callable $callback): self {
        $groupBox = new RoutesBox();
        $callback($groupBox);
        
        foreach ($groupBox->getRoutes() as $route) {
            $this->addRoute($prefix . $route['path'], $route['method'], $route['handler'], $route['middlewares']);
        }
        
        return $this;
    }
    
    public function get(string $path, string $handlerClass, array $middlewaresClasses): void {
        $this->addRoute(self::GET_METHOD, $path, $handlerClass, $middlewaresClasses);
    }
    public function post(string $path, string $handlerClass, array $middlewaresClasses): void {
        $this->addRoute(self::POST_METHOD, $path, $handlerClass, $middlewaresClasses);
    }
    public function patch(string $path, string $handlerClass, array $middlewaresClasses): void {
        $this->addRoute(self::PATCH_METHOD, $path, $handlerClass, $middlewaresClasses);
    }
    public function delete(string $path, string $handlerClass, array $middlewaresClasses): void {
        $this->addRoute(self::DELETE_METHOD, $path, $handlerClass, $middlewaresClasses);
    }
    public function put(string $path, string $handlerClass, array $middlewaresClasses): void {
        $this->addRoute(self::PUT_METHOD, $path, $handlerClass, $middlewaresClasses);
    }
}