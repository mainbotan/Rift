<?php

namespace Rift\Core\Router;

// Контракт ответа
use Rift\Core\Contracts\Response;
use Rift\Core\Contracts\ResponseDTO;

class Resolver extends Response {
    public function __construct(
        public array $routes
    ){ }

    /**
     * Обработка входящего запроса
     */
    public function execute(
        string $method,
        string $uri,
        array|null $data
    ): ResponseDTO {
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
                $params = array_merge($params, $queryParams);

                return parent::response(
                    [
                        'handler' => $route['handler'],
                        'params' => $params,
                        'payload' => $data,
                    ],
                    self::HTTP_OK
                );
            }
        }

        return parent::response(
            null,
            self::HTTP_NOT_FOUND,
            'path not found'
        );
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