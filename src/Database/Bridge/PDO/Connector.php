<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Getting PDO connections to database schemas.
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Core\Database\Bridge\PDO;

use PDO;
use PDOException;
use Rift\Contracts\Database\Bridge\PDO\ConnectorInterface;
use Rift\Core\Databus\Operation;
use Rift\Core\Databus\OperationOutcome;

final class Connector implements ConnectorInterface
{
    private const DEFAULT_OPTIONS = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    public function __construct(
        private string $driver,
        private string $host,
        private int $port,
        private string $username,
        private string $password,
        private string $defaultDatabase
    ) {
    }

    public static function fromEnv(array $env): self
    {
        return new self(
            $env['DB_DRIVER'] ?? 'pgsql',
            $env['DB_HOST'] ?? 'localhost',
            (int) ($env['DB_PORT'] ?? self::getDefaultPort($_ENV['DB_DRIVER'] ?? 'pgsql')),
            $env['DB_USER'] ?? 'root',
            $env['DB_PASSWORD'] ?? '',
            $env['DB_NAME'] ?? 'rift'
        );
    }

    public function createAdminConnection(): OperationOutcome
    {
        return $this->createConnection($this->defaultDatabase);
    }

    public function createSchemaConnection(string $schema): OperationOutcome
    {
        return $this->createConnection(
            $this->isPostgreSQL() ? $this->defaultDatabase : "{$this->defaultDatabase}_{$schema}",
            $this->isPostgreSQL() ? $schema : null
        );
    }

    private function createConnection(string $database, ?string $schema = null): OperationOutcome
    {
        try {
            $dsn = $this->buildDSN($database, $schema);
            $pdo = new PDO($dsn, $this->username, $this->password, self::DEFAULT_OPTIONS);
            
            if ($this->isPostgreSQL() && $schema) {
                $pdo->exec("SET search_path TO $schema");
            }

            return Operation::success($pdo);
        } catch (PDOException $e) {
            return Operation::error(
                Operation::HTTP_INTERNAL_SERVER_ERROR,
                'Database connection failed',
                [
                    'database' => $database,
                    'schema' => $schema,
                    'error' => $e->getMessage()
                ]
            );
        }
    }

    private function buildDSN(string $database, ?string $schema): string
    {
        $baseDSN = "{$this->driver}:host={$this->host};port={$this->port};dbname={$database}";

        if ($this->isPostgreSQL() && $schema) {
            $baseDSN .= ";options=--search_path={$schema}";
        } elseif ($this->isMySQL()) {
            $baseDSN .= ';charset=utf8mb4';
        }
        return $baseDSN;
    }

    private function isPostgreSQL(): bool
    {
        return $this->driver === 'pgsql';
    }

    private function isMySQL(): bool
    {
        return $this->driver === 'mysql';
    }

    private static function getDefaultPort(string $driver): int
    {
        return match ($driver) {
            'mysql' => 3306,
            'pgsql' => 5432,
            default => throw new \InvalidArgumentException("Unsupported database driver: {$driver}")
        };
    }
}
