<?php

namespace Rift\Core\Database\Migrations;

use PDO;
use PDOException;
use Rift\Contracts\Database\Bridge\PDO\ConnectorInterface;
use Rift\Contracts\Database\Migrations\DispatcherInterface;
use Rift\Core\Database\Models\Versioning\VersionModel;
use Rift\Core\Database\Models\Versioning\VersionRepository;
use Rift\Core\Databus\Operation;
use Rift\Core\Databus\OperationOutcome;

final class Dispatcher implements DispatcherInterface {
    
    const DEFAYLT_SYSTEM_SCHEMA = 'system';
    
    protected array $models = [];
    protected string $schema;
    public array $logs = [];
    private VersionModel $versionModel;
    private VersionRepository $versionRepository;
    private PDO $schemaConnection;

    public function __construct(
        private string $dbDriver = 'pgsql',
        private string $dbName,
        private ConnectorInterface $connector
    ) {
        $this->versionModel = new VersionModel;
     }

    // *****TENANT/SYSTEM DEPLOYMENT*****

    public function forTenant(string $tenantId, string $prefix = 'tenant_'): self
    {
        return $this->forSchema($prefix . $tenantId);
    }
    public function forSystem(string $systemSchemaName = self::DEFAYLT_SYSTEM_SCHEMA): self
    {
        return $this->forSchema($systemSchemaName);
    }
    public function forSchema(string $schemaName): self 
    {
        $this->schema = $schemaName;
        return $this;
    }

    // *****MODELS REGISTRATION*****

    public function model(string $modelLink): self 
    {
        $this->models[] = $modelLink;
        return $this;
    }

    // *****CONFIGURATION PROCESS*****

    public function configure(): OperationOutcome 
    {
        return $this->createSchema()
            ->then(fn() => $this->saveSchemaConnection())
            ->tap(fn() => $this->initVersionRepository())
            ->then(fn() => $this->migrateVersionsModel())
            ->then(fn() => $this->migrateModels());
    }

    private function createSchema(): OperationOutcome
    {
        return $this->connector->createAdminConnection()
            ->then(function ($pdo) {
                $sql = match ($this->dbDriver) {
                    'pgsql' => "CREATE SCHEMA IF NOT EXISTS {$this->schema}",
                    'mysql' => "CREATE DATABASE IF NOT EXISTS {$this->dbName}_{$this->schema}",
                    default => throw new \RuntimeException("Unsupported DB driver: {$this->dbDriver}")
                };
                try {
                    $pdo->exec($sql);
                    return Operation::success("Schema {$this->schema} created.");
                } catch (PDOException $e) {
                    return Operation::error(Operation::HTTP_INTERNAL_SERVER_ERROR, "Schema deployment error.", [
                        'exception' => $e
                    ]);
                }
            });
    }
    private function saveSchemaConnection(): OperationOutcome
    {
        return $this->connector->createSchemaConnection($this->schema)
            ->then(function (PDO $pdo) {
                $this->schemaConnection = $pdo;
                return Operation::success("Schema connection saved");
            });
    }
    private function migrateVersionsModel(): OperationOutcome 
    {   
        try {
            $this->schemaConnection->beginTransaction();
            $this->schemaConnection->exec($this->versionModel->migrate());
            $this->schemaConnection->commit();
            return Operation::success("Versions table created.");
        } catch (PDOException $e) {
            $this->schemaConnection->rollBack();
            return Operation::error(Operation::HTTP_INTERNAL_SERVER_ERROR, "Error in creating a version table for schema {$this->schema}", [
                'exception' => $e
            ]);
        }
    }
    private function initVersionRepository(): void {
        $this->versionRepository = new VersionRepository(
            $this->schemaConnection, 
            $this->versionModel
        );
    }

    private function migrateModels(): OperationOutcome
    {
        foreach ($this->models as $model) {
            $model = new $model;
            $modelName = $model::NAME;
            $modelVersion = $model::VERSION;
            
            // Ждём результат getTableVersion
            $currentTableVersion = $this->versionRepository->getTableVersion($modelName)->result;
            
            if ($currentTableVersion === null) {
                try {
                    $this->schemaConnection->beginTransaction();
                    $this->schemaConnection->exec($model->migrate());
                    $this->schemaConnection->rollBack();
                    $this->versionRepository->createTable($modelName, $modelVersion);
                    $this->logs[$modelName] = "init version tag for {$modelName} {$modelVersion}";
                } catch (PDOException $e) {
                    return Operation::error(Operation::HTTP_INTERNAL_SERVER_ERROR, "Error migrate model {$modelName}:{$modelVersion}", [
                        'exception' => $e
                    ]);
                }
            } elseif ($currentTableVersion !== $modelVersion) {
                try {
                    $this->schemaConnection->beginTransaction();
                    $this->schemaConnection->exec($model->migrate());
                    $this->schemaConnection->rollBack();
                    $this->versionRepository->updateTable($modelName, $modelVersion);
                    $this->logs[$modelName] = "from version {$currentTableVersion} to {$modelVersion}";
                } catch (PDOException $e) {
                    return Operation::error(Operation::HTTP_INTERNAL_SERVER_ERROR, "Error migrate model {$modelName}:{$modelVersion}", [
                        'exception' => $e
                    ]);
                }
            }
        }
        return Operation::success("Successful migration.");
    }
}