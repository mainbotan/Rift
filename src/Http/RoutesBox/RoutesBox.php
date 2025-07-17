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
    protected ?int $lastRouteIndex = null;

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
        if ($this->lastRouteIndex !== null) {
            $this->routes[$this->lastRouteIndex]['limit'] = [
                'max_attempts' => $maxAttempts,
                'interval' => $interval,
                'strategy' => $strategy
            ];
        } elseif (!empty($this->groupStack)) {
            $lastGroupIndex = count($this->groupStack) - 1;
            $this->groupStack[$lastGroupIndex]['limit'] = [
                'max_attempts' => $maxAttempts,
                'interval' => $interval,
                'strategy' => $strategy
            ];
        } else {
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
        if ($this->pendingLimit !== null) {
            return $this->pendingLimit;
        }
        
        foreach (array_reverse($this->groupStack) as $group) {
            if (isset($group['limit'])) {
                return $group['limit'];
            }
        }
        
        return null;
    }

    protected function addRoute(string $method, string $path, string $handler): self {
        $this->lastRouteIndex = count($this->routes);
        
        $route = [
            'method' => $method,
            'path' => $this->applyGroupPrefix($path),
            'handler' => $handler,
            'middlewares' => $this->resolveMiddlewares(),
            'limit' => $this->resolveLimit()
        ];
        
        $this->routes[] = $route;
        
        // Сбрасываем pending middlewares только если они были применены
        if ($this->pendingMiddlewares !== null && empty($this->groupStack)) {
            $this->pendingMiddlewares = null;
        }
        
        $this->pendingLimit = null;
        
        return $this;
    }

    public function middleware(array|string $middlewares): self {
        $middlewares = is_array($middlewares) ? $middlewares : [$middlewares];
        
        if ($this->lastRouteIndex !== null) {
            // Добавляем middleware к последнему созданному маршруту
            $this->routes[$this->lastRouteIndex]['middlewares'] = array_merge(
                $this->routes[$this->lastRouteIndex]['middlewares'] ?? [],
                $middlewares
            );
        } elseif (!empty($this->groupStack)) {
            // Добавляем middleware к текущей группе
            $lastGroupIndex = count($this->groupStack) - 1;
            $this->groupStack[$lastGroupIndex]['middlewares'] = array_merge(
                $this->groupStack[$lastGroupIndex]['middlewares'] ?? [],
                $middlewares
            );
        } else {
            // Сохраняем как pending для следующего маршрута/группы
            $this->pendingMiddlewares = array_merge(
                $this->pendingMiddlewares ?? [],
                $middlewares
            );
        }
        
        return $this;
    }

    public function group(string $prefix, callable $callback): self {
        $previousMiddlewares = $this->pendingMiddlewares;
        $previousLimit = $this->pendingLimit;
        $previousLastRouteIndex = $this->lastRouteIndex;
        
        $this->groupStack[] = [
            'prefix' => $prefix,
            'middlewares' => $this->pendingMiddlewares ?? [],
            'limit' => $this->pendingLimit
        ];
        
        $this->pendingMiddlewares = null;
        $this->pendingLimit = null;
        $this->lastRouteIndex = null;
        
        $callback($this);
        
        array_pop($this->groupStack);
        $this->pendingMiddlewares = $previousMiddlewares;
        $this->pendingLimit = $previousLimit;
        $this->lastRouteIndex = $previousLastRouteIndex;
        
        return $this;
    }

    protected function resolveMiddlewares(): array {
        $middlewares = [];
        
        // Middleware из групп
        foreach ($this->groupStack as $group) {
            $middlewares = array_merge($middlewares, $group['middlewares'] ?? []);
        }
        
        // Middleware текущего маршрута (только если нет активной группы)
        if ($this->pendingMiddlewares !== null && empty($this->groupStack)) {
            $middlewares = array_merge($middlewares, $this->pendingMiddlewares);
        }
        
        return array_unique($middlewares);
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