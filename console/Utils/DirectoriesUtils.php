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

use Rift\Core\DataBus\Operation;
use Rift\Core\DataBus\OperationOutcome;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

class DirectoriesUtils extends Operation
{
    public const DEFAULT_DIR_MODE = 0755;
    public const DEFAULT_FILE_MODE = 0644;

    /* ======================== */
    /* ===== ФАЙЛЫ ============ */
    /* ======================== */

    public static function fileExists(string $path): OperationOutcome
    {
        return self::success(file_exists($path) && is_file($path));
    }

    public static function removeFile(string $path): OperationOutcome
    {
        $exists = self::fileExists($path);
        if (!$exists->result) {
            return $exists;
        }

        if (!is_writable($path)) {
            return self::error(self::HTTP_FORBIDDEN, "File is not writable: {$path}");
        }

        return @unlink($path) 
            ? self::success(null)
            : self::error(self::HTTP_INTERNAL_SERVER_ERROR, "Failed to remove file: {$path}");
    }

    public static function copyFile(string $source, string $destination, bool $overwrite = false): OperationOutcome
    {
        $exists = self::fileExists($source);
        if (!$exists->result) {
            return $exists;
        }

        if (file_exists($destination) && !$overwrite) {
            return self::error(self::HTTP_CONFLICT, "Destination file already exists: {$destination}");
        }

        $validation = self::validatePathSafety($destination, dirname($destination));
        if (!$validation->isSuccess()) {
            return $validation;
        }

        if (!@copy($source, $destination)) {
            return self::error(self::HTTP_INTERNAL_SERVER_ERROR, "Failed to copy file to: {$destination}");
        }

        @chmod($destination, self::DEFAULT_FILE_MODE);
        return self::success(null);
    }

    public static function copyFileToDirectory(
        string $sourceFile,
        string $targetDir,
        ?string $newName = null,
        bool $overwrite = false
    ): OperationOutcome {
        $sourceCheck = self::fileExists($sourceFile);
        if (!$sourceCheck->isSuccess()) {
            return $sourceCheck;
        }

        $targetDir = rtrim($targetDir, DIRECTORY_SEPARATOR);
        $fileName = $newName ?? basename($sourceFile);
        $destination = $targetDir . DIRECTORY_SEPARATOR . $fileName;

        $pathValidation = self::validatePathSafety($destination, $targetDir);
        if (!$pathValidation->isSuccess()) {
            return $pathValidation;
        }

        $dirCreation = self::createDirectory($targetDir);
        if (!$dirCreation->isSuccess()) {
            return $dirCreation;
        }

        return self::copyFile($sourceFile, $destination, $overwrite);
    }

    public static function moveFile(string $source, string $destination, bool $overwrite = false): OperationOutcome
    {
        $sourceCheck = self::fileExists($source);
        if (!$sourceCheck->isSuccess()) {
            return $sourceCheck;
        }

        if (file_exists($destination)) {
            if (!$overwrite) {
                return self::error(self::HTTP_CONFLICT, "Destination file already exists: {$destination}");
            }
            
            $removal = self::removeFile($destination);
            if (!$removal->isSuccess()) {
                return $removal;
            }
        }

        $validation = self::validatePathSafety($destination, dirname($destination));
        if (!$validation->isSuccess()) {
            return $validation;
        }

        return @rename($source, $destination)
            ? self::success(null)
            : self::error(self::HTTP_INTERNAL_SERVER_ERROR, "Failed to move file to: {$destination}");
    }

    public static function readFileContent(string $path): OperationOutcome
    {
        $exists = self::fileExists($path);
        if (!$exists->isSuccess()) {
            return $exists;
        }

        $content = @file_get_contents($path);
        return $content !== false
            ? self::success($content)
            : self::error(self::HTTP_INTERNAL_SERVER_ERROR, "Failed to read file: {$path}");
    }

    public static function writeFileContent(string $path, string $content, bool $overwrite = false): OperationOutcome
    {
        if (file_exists($path) && !$overwrite) {
            return self::error(self::HTTP_CONFLICT, "File already exists: {$path}");
        }

        $validation = self::validatePathSafety($path, dirname($path));
        if (!$validation->isSuccess()) {
            return $validation;
        }

        if (@file_put_contents($path, $content) === false) {
            return self::error(self::HTTP_INTERNAL_SERVER_ERROR, "Failed to write file: {$path}");
        }

        @chmod($path, self::DEFAULT_FILE_MODE);
        return self::success(null);
    }

    /* ======================== */
    /* ===== ДИРЕКТОРИИ ======= */
    /* ======================== */

    public static function getStubsDir(string $dirName): OperationOutcome
    {
        $dir = dirname(__DIR__, 3);
        $path = "{$dir}/stubs/{$dirName}";
        
        if (!is_dir($path)) {
            return self::error(self::HTTP_NOT_FOUND, "Stubs directory not found: {$path}");
        }
        
        return self::success(realpath($path));
    }

    public static function createDirectory(string $path, int $mode = null): OperationOutcome
    {
        if (is_dir($path)) {
            return self::success(null);
        }

        return @mkdir($path, $mode ?? self::DEFAULT_DIR_MODE, true)
            ? self::success(null)
            : self::error(self::HTTP_INTERNAL_SERVER_ERROR, "Failed to create directory: {$path}");
    }

    public static function createTempDirectory(string $prefix = ''): OperationOutcome
    {
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $prefix . uniqid();
        return self::createDirectory($tempDir)
            ->withMetric('temp_dir', $tempDir);
    }

    public static function copyDirectory(string $source, string $destination, bool $overwrite = false): OperationOutcome
    {
        $source = realpath($source);
        if (!is_dir($source)) {
            return self::error(self::HTTP_NOT_FOUND, "Source directory does not exist: {$source}");
        }

        $dirCreation = self::createDirectory($destination);
        if (!$dirCreation->isSuccess()) {
            return $dirCreation;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $filesCopied = 0;
        $errors = [];

        foreach ($iterator as $item) {
            $targetPath = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();

            $validation = self::validatePathSafety($targetPath, $destination);
            if (!$validation->isSuccess()) {
                $errors[] = $validation->error;
                continue;
            }

            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    $result = self::createDirectory($targetPath);
                    if (!$result->isSuccess()) {
                        $errors[] = $result->error;
                    }
                }
            } else {
                if ($overwrite || !file_exists($targetPath)) {
                    $result = self::copyFile($item->getPathname(), $targetPath, $overwrite);
                    if ($result->isSuccess()) {
                        $filesCopied++;
                    } else {
                        $errors[] = $result->error;
                    }
                }
            }
        }

        return empty($errors)
            ? self::success(null, ['files_copied' => $filesCopied])
            : self::error(
                self::HTTP_PARTIAL_CONTENT,
                "Copied {$filesCopied} files with errors",
                ['errors' => $errors]
            );
    }

    public static function cleanDirectory(string $dir): OperationOutcome
    {
        if (!is_dir($dir)) {
            return self::error(self::HTTP_NOT_FOUND, "Directory does not exist: {$dir}");
        }

        if (!is_writable($dir)) {
            return self::error(self::HTTP_FORBIDDEN, "Directory is not writable: {$dir}");
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        $filesRemoved = 0;
        $dirsRemoved = 0;
        $errors = [];

        foreach ($iterator as $item) {
            $path = $item->getPathname();
            try {
                if ($item->isDir()) {
                    if (@rmdir($path)) {
                        $dirsRemoved++;
                    } else {
                        $errors[] = "Failed to remove directory: {$path}";
                    }
                } else {
                    if (@unlink($path)) {
                        $filesRemoved++;
                    } else {
                        $errors[] = "Failed to remove file: {$path}";
                    }
                }
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        return empty($errors)
            ? self::success(null, [
                'files_removed' => $filesRemoved,
                'dirs_removed' => $dirsRemoved
            ])
            : self::error(
                self::HTTP_PARTIAL_CONTENT,
                "Cleaned directory with errors",
                [
                    'files_removed' => $filesRemoved,
                    'dirs_removed' => $dirsRemoved,
                    'errors' => $errors
                ]
            );
    }

    public static function removeDirectory(string $dir): OperationOutcome
    {
        $cleanResult = self::cleanDirectory($dir);
        if (!$cleanResult->isSuccess()) {
            return $cleanResult;
        }

        return @rmdir($dir)
            ? self::success(null)
            : self::error(self::HTTP_INTERNAL_SERVER_ERROR, "Failed to remove directory: {$dir}");
    }

    public static function remove(string $path): OperationOutcome
    {
        if (is_dir($path)) {
            return self::removeDirectory($path);
        }

        $exists = self::fileExists($path);
        if ($exists->isSuccess()) {
            return self::removeFile($path);
        }

        return $exists;
    }

    public static function isEmptyDirectory(string $dir): OperationOutcome
    {
        if (!is_dir($dir)) {
            return self::error(self::HTTP_NOT_FOUND, "Directory does not exist: {$dir}");
        }

        $iterator = new FilesystemIterator($dir);
        return self::success(!$iterator->valid());
    }

    public static function getFilesList(string $directory, bool $recursive = true): OperationOutcome
    {
        if (!is_dir($directory)) {
            return self::error(self::HTTP_NOT_FOUND, "Directory does not exist: {$directory}");
        }

        $files = [];
        $iterator = $recursive 
            ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory))
            : new FilesystemIterator($directory);

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }

        return self::success($files);
    }

    public static function getDirectorySize(string $directory): OperationOutcome
    {
        if (!is_dir($directory)) {
            return self::error(self::HTTP_NOT_FOUND, "Directory does not exist: {$directory}");
        }

        $size = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return self::success($size);
    }

    /* ======================== */
    /* ===== СЛУЖЕБНЫЕ ======== */
    /* ======================== */

    protected static function validatePathSafety(string $targetPath, string $baseDir): OperationOutcome
    {
        $realBaseDir = realpath($baseDir);
        $realTargetPath = realpath(dirname($targetPath));

        if ($realBaseDir === false || $realTargetPath === false) {
            return self::error(self::HTTP_BAD_REQUEST, "Invalid path detected: {$targetPath}");
        }

        $realBaseDir = str_replace('\\', '/', $realBaseDir);
        $realTargetPath = str_replace('\\', '/', $realTargetPath);

        if (strpos($realTargetPath . '/', $realBaseDir . '/') !== 0) {
            return self::error(self::HTTP_FORBIDDEN, "Path traversal attempt detected: {$targetPath}");
        }

        return self::success(null);
    }
}