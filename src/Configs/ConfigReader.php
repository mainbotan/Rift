<?php

namespace Rift\Core\Configs;

use Exception;
use Rift\Core\Contracts\Operation;
use Rift\Core\Contracts\OperationOutcome;

class ConfigReader extends Operation
{
    private static array $cache = [];
    private static array $resolvedEnv = [];

    public static function get(string $key, mixed $default = null): OperationOutcome
    {
        $segments = explode('.', $key);
        $configName = array_shift($segments);
        
        try {
            $config = self::load($configName);
        } catch (Exception $e) {
            return self::error(self::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
        
        $current = $config;

        foreach ($segments as $segment) {
            if (!isset($current[$segment])) {
                if ($default !== null) {
                    return self::success($default);
                }
                return self::error(self::HTTP_INTERNAL_SERVER_ERROR, "Config key not found: {$key}");
            }
            $current = $current[$segment];
        }

        return self::success($current);
    }

    /**
     * Загружает конфиг из файла с кешированием
     */
    private static function load(string $configName): array
    {
        if (isset(self::$cache[$configName])) {
            return self::$cache[$configName];
        }

        $path = self::getConfigPath($configName);
        $config = require $path;

        return self::$cache[$configName] = self::resolveEnv($config);
    }

    /**
     * Заменяет $_ENV-переменные в конфиге на реальные значения
     */
    private static function resolveEnv(array $config): array
    {
        array_walk_recursive($config, function (&$value) {
            if (is_string($value) && str_starts_with($value, '$_ENV[')) {
                $envKey = trim(substr($value, 7, -2)); // Извлекаем 'JWT_SECRET' из строки '$_ENV[JWT_SECRET]'
                $value = $_ENV[$envKey] ?? throw new \RuntimeException("Env variable not set: {$envKey}");
            }
        });

        return $config;
    }

    private static function getConfigPath(string $name): string
    {
        $path = getcwd() . "/configs/{$name}.rift.php";
        if (!file_exists($path)) {
            throw new \RuntimeException("Config file not found: {$path}");
        }
        return $path;
    }
}