<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Initialization of the scheme with tables.
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Core\Database\Configurators;

use PDO;
use PDOException;
use Rift\Core\Databus\Operation;
use Rift\Core\Databus\OperationOutcome;
use Rift\Contracts\Database\Bridge\PDO\ConnectorInterface;
use Rift\Contracts\Database\Configurators\ConfiguratorInterface;

final class Configurator implements ConfiguratorInterface
{
    private static array $tenantModels = [];
    private static array $systemModels = [];

    private string $targetSchema;
    private bool $isSystemSchema = false;
    private bool $skipSchemaCreation = false;

    public function __construct(
        private ConnectorInterface $connector
    ) {}

    public static function registerTenantModel(string $modelClass): void
    {
        self::validateModel($modelClass);
        self::$tenantModels[$modelClass] = $modelClass;
    }

    public static function registerSystemModel(string $modelClass): void
    {
        self::validateModel($modelClass);
        self::$systemModels[$modelClass] = $modelClass;
    }

    public function forTenant(string $tenantId, string $prefix = 'tenant_'): self
    {
        if ($tenantId === 'system') {
            throw new \InvalidArgumentException(
                'Use forSystem() method to configure system schema'
            );
        }

        $this->targetSchema = $prefix . $tenantId;
        $this->isSystemSchema = false;
        return $this;
    }

    public function forSystem(): self
    {
        $this->targetSchema = 'system';
        $this->isSystemSchema = true;
        return $this;
    }

    public function skipSchemaCreation(): self
    {
        $this->skipSchemaCreation = true;
        return $this;
    }

    public function configure(): OperationOutcome
    {
        if (empty($this->targetSchema)) {
            return Operation::error(
                Operation::HTTP_BAD_REQUEST,
                'Schema must be specified'
            );
        }

        try {
            $models = $this->isSystemSchema ? self::$systemModels : self::$tenantModels;

            // 1. Создаем схему (если не пропущено)
            if (!$this->skipSchemaCreation) {
                $this->createSchema($this->targetSchema);
            }

            // 2. Накатываем миграции
            return $this->migrateModels($this->targetSchema, $models);

        } catch (PDOException $e) {
            return Operation::error(
                Operation::HTTP_INTERNAL_SERVER_ERROR,
                "Schema configuration failed for '{$this->targetSchema}'",
                [
                    'error' => $e->getMessage(),
                    'schema' => $this->targetSchema,
                    'is_system' => $this->isSystemSchema
                ]
            );
        }
    }

    private static function validateModel(string $modelClass): void
    {
        if (!method_exists($modelClass, 'getMigrationSQL')) {
            throw new \RuntimeException(
                "Model {$modelClass} must implement getMigrationSQL() method"
            );
        }
    }

    private function createSchema(string $schema): void
    {
        $adminConnection = $this->connector->createAdminConnection();
        
        if (!$adminConnection->isSuccess()) {
            throw new PDOException(
                $adminConnection->error ?? 'Admin connection failed'
            );
        }

        $adminPdo = $adminConnection->result;
        $driver = $_ENV['DB_DRIVER'] ?? 'pgsql';

        $sql = match ($driver) {
            'pgsql' => "CREATE SCHEMA IF NOT EXISTS {$schema}",
            'mysql' => "CREATE DATABASE IF NOT EXISTS {$_ENV['DB_NAME']}_{$schema}",
            default => throw new \RuntimeException("Unsupported DB driver: {$driver}")
        };

        $adminPdo->exec($sql);
    }

    private function migrateModels(string $schema, array $models): OperationOutcome
    {
        if (empty($models)) {
            return Operation::success(
                "No models to migrate for schema '{$schema}'"
            );
        }

        $connection = $this->connector->createSchemaConnection($schema);
        
        if (!$connection->isSuccess()) {
            return $connection;
        }

        $pdo = $connection->result;

        foreach ($models as $model) {
            try {
                $pdo->exec($model::getMigrationSQL($schema));
            } catch (PDOException $e) {
                return Operation::error(
                    Operation::HTTP_INTERNAL_SERVER_ERROR,
                    "Migration failed for model: {$model}",
                    [
                        'model' => $model,
                        'error' => $e->getMessage(),
                        'schema' => $schema
                    ]
                );
            }
        }

        return Operation::success(
            "Schema '{$schema}' configured with " . count($models) . " models"
        );
    }
}
