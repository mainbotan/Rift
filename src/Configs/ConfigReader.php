<?php

/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Reading configuration files.
 * |
 * |--------------------------------------------------------------------------
 */

namespace Rift\Core\Configs;

use Exception;
use PhpParser\Node\Stmt\Echo_;
use Rift\Core\Databus\Operation;
use Rift\Core\Databus\OperationOutcome;

class ConfigReader extends Operation
{
    private static array $cache = [];
    private static ?string $basePath = null;

    /**
     * Устанавливает базовый путь для конфигов (по умолчанию getcwd() . '/configs')
     */
    public static function setBasePath(?string $path): void
    {
        self::$basePath = $path;
    }

    /**
     * Получает несколько значений за один запрос
     * 
     * @param array $keys Массив ключей в формате 'file.key.subkey'
     * @return OperationOutcome Успех с массивом значений или ошибка
     */
    public static function getMany(array $keys): OperationOutcome
    {
        $results = [];
        $errors = [];

        foreach ($keys as $key) {
            $result = self::get($key);
            if ($result->isSuccess()) {
                $results[$key] = $result->result;
            } else {
                $errors[$key] = $result->error;
            }
        }

        return empty($errors)
            ? self::success($results)
            : self::error(
                self::HTTP_PARTIAL_CONTENT,
                'Some config keys could not be resolved',
                ['results' => $results, 'errors' => $errors]
            );
    }

    /**
     * Получает значение конфига с поддержкой:
     * - Вложенных ключей через точку
     * - Переменных окружения
     * - Кастомных обработчиков значений
     */
    public static function get(string $key, mixed $default = null): OperationOutcome
    {
        try {
            [$configName, $path] = self::parseKey($key);
            $config = self::loadConfig($configName);
            $value = self::resolveValue($config, $path, $default);

            return self::success($value);
        } catch (Exception $e) {
            return $default !== null
                ? self::success($default)
                : self::error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * Парсит ключ на имя конфига и путь к значению
     */
    private static function parseKey(string $key): array
    {
        if (!str_contains($key, '.')) {
            throw new Exception("Invalid config key format: {$key}");
        }

        [$configName, $path] = explode('.', $key, 2);
        return [$configName, explode('.', $path)];
    }

    /**
     * Загружает конфиг с кешированием
     */
    private static function loadConfig(string $name): array
    {
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }

        $path = self::getConfigPath($name);
        $config = require $path;

        return self::$cache[$name] = self::processConfig($config);
    }

    /**
     * Обрабатывает конфиг:
     * - Подставляет переменные окружения
     * - Вычисляет динамические значения
     */
    private static function processConfig(array $config): array
    {
        array_walk_recursive($config, function (&$value) {
            if (is_string($value)) {
                $value = self::resolveStringValue($value);
            }
        });

        return $config;
    }

    /**
     * Обрабатывает строковые значения:
     * - Переменные окружения: ${ENV_KEY}, $_ENV[ENV_KEY]
     * - Динамические вызовы: @func(args)
     */
    private static function resolveStringValue(string $value): mixed
    {
        // Обработка ${ENV_KEY}
        if (preg_match('/^\${([A-Z0-9_]+)}$/', $value, $matches)) {
            return $_ENV[$matches[1]] ?? throw new Exception("Env variable not found: {$matches[1]}");
        }

        // Обработка $_ENV[ENV_KEY]
        if (str_starts_with($value, '$_ENV[')) {
            $envKey = trim(substr($value, 7, -2));
            return $_ENV[$envKey] ?? throw new Exception("Env variable not found: {$envKey}");
        }

        // Обработка @func(args)
        if (str_starts_with($value, '@')) {
            return self::callDynamicHandler($value);
        }

        return $value;
    }

    /**
     * Извлекает значение из конфига по пути
     */
    private static function resolveValue(array $config, array $path, mixed $default): mixed
    {
        $current = $config;

        foreach ($path as $segment) {
            if (!isset($current[$segment])) {
                if ($default !== null) {
                    return $default;
                }
                throw new Exception("Config path not found: " . implode('.', $path));
            }
            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * Возвращает полный путь к файлу конфига
     */
    private static function getConfigPath(string $name): string
    {
        $base = self::$basePath ?? getcwd() . '/configs';
        $path = "{$base}/{$name}.php";

        if (!file_exists($path)) {
            throw new Exception("Config file not found: {$path}");
        }

        return $path;
    }

    /**
     * Вызывает кастомный обработчик значений
     */
    private static function callDynamicHandler(string $value): mixed
    {
        // Пример обработки @env(KEY) → $_ENV[KEY]
        if (preg_match('/^@env\(([A-Z0-9_]+)\)$/i', $value, $matches)) {
            return $_ENV[$matches[1]] ?? throw new Exception("Env variable not found: {$matches[1]}");
        }

        // Можно добавить другие обработчики...

        throw new Exception("Unknown value handler: {$value}");
    }
}