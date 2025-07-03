<?php

namespace Rift\Core\Http;

use Rift\Core\Contracts\Operation;
use Rift\Core\Contracts\OperationOutcome;

class RoutesBox {
    protected array $routes = [];
    protected array $groupStack = [];
    protected ?array $pendingMiddlewares = null;

    public function get(string $path, string $handler): self {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, string $handler): self {
        return $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, string $handler): self {
        return $this->addRoute('PUT', $path, $handler);
    }

    public function patch(string $path, string $handler): self {
        return $this->addRoute('PATCH', $path, $handler);
    }

    public function delete(string $path, string $handler): self {
        return $this->addRoute('DELETE', $path, $handler);
    }

    public function group(string $prefix, callable $callback): self {
        // Сохраняем текущий стек middleware
        $previousMiddlewares = $this->pendingMiddlewares;
        
        $this->groupStack[] = [
            'prefix' => $prefix,
            'middlewares' => $this->pendingMiddlewares ?? []
        ];
        
        $this->pendingMiddlewares = null;
        $callback($this);
        array_pop($this->groupStack);
        
        // Восстанавливаем предыдущие middleware
        $this->pendingMiddlewares = $previousMiddlewares;
        
        return $this;
    }

    protected function addRoute(string $method, string $path, string $handler): self {
        $route = [
            'method' => $method,
            'path' => $this->applyGroupPrefix($path),
            'handler' => $handler,
            'middlewares' => $this->resolveMiddlewares()
        ];
        
        $this->routes[] = $route;
        
        return $this;
    }

    protected function resolveMiddlewares(): array {
        $middlewares = [];
        
        // Middleware из групп
        foreach ($this->groupStack as $group) {
            if (!empty($group['middlewares'])) {
                $middlewares = array_merge($middlewares, $group['middlewares']);
            }
        }
        
        // Middleware текущего маршрута
        if ($this->pendingMiddlewares) {
            $middlewares = array_merge($middlewares, $this->pendingMiddlewares);
        }
        
        return array_unique($middlewares);
    }

    public function middleware(array|string $middlewares): self {
        $middlewares = is_array($middlewares) ? $middlewares : [$middlewares];
        
        if (empty($this->groupStack) && empty($this->routes)) {
            // Если нет активных групп и маршрутов, сохраняем для следующего маршрута
            $this->pendingMiddlewares = array_merge(
                $this->pendingMiddlewares ?? [],
                $middlewares
            );
        } elseif (!empty($this->groupStack)) {
            // Добавляем middleware к последней группе в стеке
            $lastIndex = count($this->groupStack) - 1;
            $this->groupStack[$lastIndex]['middlewares'] = array_merge(
                $this->groupStack[$lastIndex]['middlewares'] ?? [],
                $middlewares
            );
        } else {
            // Добавляем middleware к последнему добавленному маршруту
            $lastIndex = count($this->routes) - 1;
            if ($lastIndex >= 0) {
                $this->routes[$lastIndex]['middlewares'] = array_merge(
                    $this->routes[$lastIndex]['middlewares'] ?? [],
                    $middlewares
                );
            }
        }
        
        return $this;
    }

    protected function applyGroupPrefix(string $path): string {
        $prefix = '';
        foreach ($this->groupStack as $group) {
            $prefix .= $group['prefix'];
        }
        return rtrim($prefix, '/').'/'.ltrim($path, '/');
    }

    public function getRoutes(): array {
        return $this->routes;
    }
}