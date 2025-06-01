<?php

namespace Rift\Core\Database;

use PDO;
use Rift\Core\Contracts\Response;
use Rift\Core\Contracts\ResponseDTO;

class Connect extends Response
{
    /**
     * Развёртывание схем
     */
    public static function adminPdo(): ResponseDTO
    {
        $driver = $_ENV['DB_DRIVER'] ?? 'pgsql';

        $dsn = match ($driver) {
            'mysql' => "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}",
            'pgsql' => "pgsql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']}",
            default => null,
        };

        try {
            $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            return self::success($pdo);

        } catch (\PDOException $e) {
            return self::error(500, "Admin DB connection failed", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Подключение к системной схеме
     */
    public static function systemPdo(): ResponseDTO
    {
        return self::getPdoForSchema('system');
    }

    /**
     * Подключение к конкретной схеме
     */
    public static function getPdoForSchema(string $schema): ResponseDTO
    {
        $driver = $_ENV['DB_DRIVER'] ?? 'pgsql';
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? ($driver === 'mysql' ? 3306 : 5432);
        $user = $_ENV['DB_USER'] ?? 'root';
        $password = $_ENV['DB_PASSWORD'] ?? '';
        $dbname = $_ENV['DB_NAME'];

        // MySQL: используем как отдельную БД: rift_system
        // PostgreSQL: тот же DB_NAME, но переключаем schema search_path
        $dsn = match ($driver) {
            'mysql' => "mysql:host={$host};port={$port};dbname={$dbname}_{$schema};charset=utf8mb4",
            'pgsql' => "pgsql:host={$host};port={$port};dbname={$dbname};options=--search_path={$schema}",
        };

        try {
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            return self::success($pdo);
        } catch (\PDOException $e) {
            return self::error(500, "Schema connection failed", [
                'schema' => $schema,
                'error' => $e->getMessage()
            ]);
        }
    }
}
