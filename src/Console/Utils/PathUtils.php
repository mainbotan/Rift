<?php

namespace Rift\Core\Console\Utils;

class PathUtils
{
    public static function getProjectRoot(): string
    {
        // 1. Проверяем .env
        if (isset($_ENV['APP_ROOT']) && is_dir($_ENV['APP_ROOT'])) {
            return $_ENV['APP_ROOT'];
        }
        
        // 2. Пытаемся найти .env вверх по директориям
        $currentDir = getcwd();
        while ($currentDir !== '/') {
            if (file_exists("{$currentDir}/.env")) {
                $envContent = parse_ini_file("{$currentDir}/.env");
                if (isset($envContent['APP_ROOT']) && is_dir($envContent['APP_ROOT'])) {
                    return $envContent['APP_ROOT'];
                }
                return $currentDir;
            }
            $currentDir = dirname($currentDir);
        }
        
        // 3. Fallback на текущую директорию
        return getcwd();
    }
}