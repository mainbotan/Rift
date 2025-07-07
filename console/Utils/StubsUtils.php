<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * CLI component
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Console\Utils;

use Rift\Core\Databus\OperationOutcome;
use Rift\Core\Databus\Operation;

class StubsUtils extends Operation
{
    public static string $stubsBasePath = '/stubs/app';

    private static function fixPermissions(string $path): bool
    {
        return is_dir($path) 
            ? @chmod($path, 0755) 
            : @chmod($path, 0644);
    }

    /**
     * Инициализирует структуру проекта
     */
    public static function initProjectStructure(
        string $targetRoot,
        bool $overwrite = false
    ): OperationOutcome {
        $sourceRoot = dirname(__DIR__, 3) . self::$stubsBasePath;
        
        return self::copyStructure(
            $sourceRoot,
            $targetRoot,
            [
                'configs/' => 'configs',
                'src/' => 'src',
                'routes.stub' => 'routes.php',
            ],
            $overwrite
        );
    }

    /**
     * Копирует структуру по карте
     */
    public static function copyStructure(
        string $sourceRoot,
        string $targetRoot,
        array $structureMap,
        bool $overwrite = false
    ): OperationOutcome {
        $result = [
            'created' => [],
            'skipped' => [],
            'errors' => []
        ];

        foreach ($structureMap as $source => $target) {
            $sourcePath = rtrim($sourceRoot, '/') . '/' . ltrim($source, '/');
            $targetPath = rtrim($targetRoot, '/') . '/' . ltrim($target, '/');

            if (is_dir($sourcePath)) {
                $copyResult = self::copyDirectory(
                    $sourcePath,
                    $targetPath,
                    $overwrite
                );
            } else {
                $copyResult = self::copyFile(
                    $sourcePath,
                    $targetPath,
                    $overwrite
                );
            }

            if (!$copyResult->isSuccess()) {
                $result['errors'][] = $copyResult->error;
                continue;
            }

            $result['created'] = array_merge($result['created'], $copyResult->result['created'] ?? []);
            $result['skipped'] = array_merge($result['skipped'], $copyResult->result['skipped'] ?? []);
        }

        if (!empty($result['errors'])) {
            return self::error(
                self::HTTP_INTERNAL_SERVER_ERROR,
                'Partial copy completed',
                $result
            );
        }

        return self::success($result);
    }

    /**
     * Копирует файл
     */
    public static function copyFile(
        string $source,
        string $target,
        bool $overwrite = false
    ): OperationOutcome {
        $target = str_replace('.stub', '', $target);

        if (!file_exists($source)) {
            return self::error(
                self::HTTP_NOT_FOUND,
                "Source file not found: {$source}"
            );
        }

        if (file_exists($target) && !$overwrite) {
            return self::success([
                'skipped' => [$target],
                'created' => []
            ]);
        }

        $dir = dirname($target);
        if (!is_dir($dir)) {
            $mkdirResult = self::ensureDirectory($dir);
            if (!$mkdirResult->isSuccess()) {
                return $mkdirResult;
            }
        }

        if (!copy($source, $target)) {
            return self::error(
                self::HTTP_INTERNAL_SERVER_ERROR,
                "Failed to copy file: {$source} → {$target}"
            );
        }

        // Вот здесь применяем исправление прав
        if (!self::fixPermissions($target)) {
            return self::error(
                self::HTTP_INTERNAL_SERVER_ERROR,
                "Failed to set permissions for: {$target}"
            );
        }

        return self::success([
            'created' => [$target],
            'skipped' => []
        ]);
    }

    /**
     * Копирует директорию рекурсивно
     */
    public static function copyDirectory(
        string $source,
        string $target,
        bool $overwrite = false
    ): OperationOutcome {
        if (!is_dir($source)) {
            return self::error(
                self::HTTP_NOT_FOUND,
                "Source directory not found: {$source}"
            );
        }

        $result = [
            'created' => [],
            'skipped' => [],
            'errors' => []
        ];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = $iterator->getSubPathname();
            $targetPath = $target . '/' . str_replace('.stub', '', $relativePath);

            if ($item->isDir()) {
                $mkdirResult = self::ensureDirectory($targetPath);
                if (!$mkdirResult->isSuccess()) {
                    $result['errors'][] = $mkdirResult->error;
                    continue;
                }
            } else {
                $copyResult = self::copyFile(
                    $item->getPathname(),
                    $targetPath,
                    $overwrite
                );

                if (!$copyResult->isSuccess()) {
                    $result['errors'][] = $copyResult->error;
                    continue;
                }

                $result['created'] = array_merge($result['created'], $copyResult->result['created'] ?? []);
                $result['skipped'] = array_merge($result['skipped'], $copyResult->result['skipped'] ?? []);
            }
        }

        // Применяем fixPermissions для самой директории
        self::fixPermissions($target);

        if (!empty($result['errors'])) {
            return self::error(
                self::HTTP_INTERNAL_SERVER_ERROR,
                'Partial directory copy completed',
                $result
            );
        }

        return self::success($result);
    }

    /**
     * Создает директорию если ее нет
     */
    public static function ensureDirectory(string $path): OperationOutcome
    {
        if (is_dir($path)) {
            return self::success(['path' => $path]);
        }

        if (!mkdir($path, 0755, true)) {
            return self::error(
                self::HTTP_INTERNAL_SERVER_ERROR,
                "Failed to create directory: {$path}"
            );
        }

        // Применяем fixPermissions для новой директории
        self::fixPermissions($path);

        return self::success(['path' => $path]);
    }
}