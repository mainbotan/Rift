<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Routes registration.
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Core\Http\RoutesBox;

use Rift\Contracts\Http\RoutesBox\RoutesBoxInterface;
use Rift\Core\Databus\Operation;
use Rift\Core\Databus\OperationOutcome;

class RoutesBox implements RoutesBoxInterface {
    protected array $routes = [];
    protected array $groupStack = [];
    protected ?array $pendingMiddlewares = null;
    protected ?array $pendingLimit = null;

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

    public function limit(int $maxAttempts, int $interval = 3600, string $strategy = 'ip+agent'): self
    {
        // Если есть маршруты, применяем к последнему
        if (!empty($this->routes)) {
            $lastIndex = count($this->routes) - 1;
            $this->routes[$lastIndex]['limit'] = [
                'max_attempts' => $maxAttempts,
                'interval' => $interval,
                'strategy' => $strategy
            ];
        } else {
            // Иначе сохраняем как pending для следующего маршрута
            $this->pendingLimit = [
                'max_attempts' => $maxAttempts,
                'interval' => $interval,
                'strategy' => $strategy
            ];
        }
        
        return $this;
    }

    protected function resolveLimit(): ?array
    {
        // Если есть локальный лимит - используем его
        if ($this->pendingLimit !== null) {
            return $this->pendingLimit;
        }
        
        // Ищем последний лимит в группах
        foreach (array_reverse($this->groupStack) as $group) {
            if (isset($group['limit'])) {
                return $group['limit'];
            }
        }
        
        return null;
    }

    protected function addRoute(string $method, string $path, string $handler): self {
        $route = [
            'method' => $method,
            'path' => $this->applyGroupPrefix($path),
            'handler' => $handler,
            'middlewares' => $this->resolveMiddlewares(),
            'limit' => $this->resolveLimit()
        ];
        
        $this->routes[] = $route;
        $this->pendingLimit = null;
        
        return $this;
    }

    public function group(string $prefix, callable $callback): self {
        $previousMiddlewares = $this->pendingMiddlewares;
        $previousLimit = $this->pendingLimit;
        
        $this->groupStack[] = [
            'prefix' => $prefix,
            'middlewares' => $this->pendingMiddlewares ?? [],
            'limit' => $this->pendingLimit
        ];
        
        $this->pendingMiddlewares = null;
        $this->pendingLimit = null;
        $callback($this);
        array_pop($this->groupStack);
        
        $this->pendingMiddlewares = $previousMiddlewares;
        $this->pendingLimit = $previousLimit;
        
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