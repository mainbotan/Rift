<?php

/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Path configurator. Initial registration of routes. 
 * [in a production environment, use pre-prepared configurations]
 * |
 * |--------------------------------------------------------------------------
 */

namespace Rift\Core\Http;

use Rift\Core\Contracts\Operation;
use Rift\Core\Contracts\OperationOutcome;

class RoutesBox extends Operation {
    const POST_METHOD = 'POST';
    const GET_METHOD = 'GET';
    const PUT_METHOD = 'PUT';
    const PATCH_METHOD = 'PATCH';
    const DELETE_METHOD = 'DELETE';

    protected ?string $currentGroupPrefix = null;
    protected array $currentGroupMiddlewares = [];

    public array $routes = [];

    public function getRoutes(): array {
        return $this->routes;
    }

    protected function addRoute(string $method, string $path, string $handler, array $middlewares) {
        $fullPath = rtrim($this->currentGroupPrefix ?? '', '/') . '/' . ltrim($path, '/');
        $fullMiddlewares = array_merge($this->currentGroupMiddlewares ?? [], $middlewares);

        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'handler' => $handler,
            'middlewares' => $fullMiddlewares
        ];
    }

    public function group(array $options, callable $callback): self {
        $prefix = $options['prefix'] ?? '';
        $middlewares = $options['middlewares'] ?? [];

        $previousPrefix = $this->currentGroupPrefix ?? '';
        $previousMiddlewares = $this->currentGroupMiddlewares ?? [];

        // Обновляем префикс и middleware для вложенных групп
        $this->currentGroupPrefix = $previousPrefix . $prefix;
        $this->currentGroupMiddlewares = array_merge($previousMiddlewares, $middlewares);

        $callback($this);

        // Восстанавливаем предыдущие значения после выхода из группы
        $this->currentGroupPrefix = $previousPrefix;
        $this->currentGroupMiddlewares = $previousMiddlewares;

        return $this;
    }
    public function get(string $path, string $handlerClass, array $middlewaresClasses): self {
        $this->addRoute(self::GET_METHOD, $path, $handlerClass, $middlewaresClasses);
        return $this;
    }
    public function post(string $path, string $handlerClass, array $middlewaresClasses): self {
        $this->addRoute(self::POST_METHOD, $path, $handlerClass, $middlewaresClasses);
        return $this;
    }
    public function patch(string $path, string $handlerClass, array $middlewaresClasses): self {
        $this->addRoute(self::PATCH_METHOD, $path, $handlerClass, $middlewaresClasses);
        return $this;
    }
    public function delete(string $path, string $handlerClass, array $middlewaresClasses): self {
        $this->addRoute(self::DELETE_METHOD, $path, $handlerClass, $middlewaresClasses);
        return $this;
    }
    public function put(string $path, string $handlerClass, array $middlewaresClasses): self {
        $this->addRoute(self::PUT_METHOD, $path, $handlerClass, $middlewaresClasses);
        return $this;
    }
}