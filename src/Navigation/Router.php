<?php

namespace Rift\Core\Navigation;

// Контракт ответа
use Rift\Core\Contracts\Operation;
use Rift\Core\Contracts\OperationOutcome;

class Router extends Operation {
    public function __construct(
        public array $routes
    ){ }

    /**
     * Обработка входящего запроса
     */
    public function execute(
        string $method,
        string $uri,
        ?array $data
    ): OperationOutcome {
        // Отрезаем query string от пути
        $parsedUri = parse_url($uri);
        $path = $parsedUri['path'] ?? '/';

        foreach ($this->routes as $route) {
            if (strtoupper($route['method']) !== strtoupper($method)) {
                continue;
            }

            [$regex, $paramNames] = $this->parseRoute($route['path']);

            if (preg_match($regex, $path, $matches)) {
                $params = [];
                foreach ($paramNames as $name) {
                    $params[$name] = $matches[$name] ?? null;
                }

                // Объединяем параметры пути и query
                parse_str($parsedUri['query'] ?? '', $queryParams);
                $params = array_merge($queryParams, $params, $data);

                // Пробегаем по мидлвейрам
                $middlewares = isset($route['middlewares']) ? $route['middlewares'] : null;
                $resultOfCheckMiddlewares = $this->checkMiddlewares($middlewares, $params);
                if ($resultOfCheckMiddlewares->code !== self::HTTP_OK) {
                    return $resultOfCheckMiddlewares;
                }
                
                // Обращаемся к выполняющему слою
                if (!isset($route['handler'])) {
                    return parent::error(self::HTTP_NOT_FOUND, "Path's handler not found");
                }

                $handler = $route['handler'];

                // Проверка: существует ли класс
                if (!class_exists($handler)) {
                    return parent::error(self::HTTP_INTERNAL_SERVER_ERROR, "Handler class {$handler} does not exist");
                }

                // Проверка: реализует ли нужный интерфейс 
                if (!in_array('Rift\Core\UseCases\UseCaseInterface', class_implements($handler))) {
                    return parent::error(self::HTTP_INTERNAL_SERVER_ERROR, "Handler {$handler} must implement UseCaseInterface");
                }

                // Создание экземпляра и вызов
                $useCase = new $handler;
                return $useCase->execute($params);
            }
        }

        return parent::response(
            null,
            self::HTTP_NOT_FOUND,
            'path not found'
        );
    }

    /**
     * Проход по мидлварам
     */
    private function checkMiddlewares(
        array|null $middlewares,
        array|null $params
    ): OperationOutcome {
        if (!is_array($middlewares) || empty($middlewares)) {
            return parent::response(null, self::HTTP_OK);
        }
        
        foreach ($middlewares as $middleware) {
            $middlewareClass = new $middleware;
            $resultOfCheckMiddleware = $middlewareClass->execute($params);
            
            // Выход при завале
            if ($resultOfCheckMiddleware->code !== self::HTTP_OK ) {
                return $resultOfCheckMiddleware;
            }
        }

        return parent::response(null, self::HTTP_OK);
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