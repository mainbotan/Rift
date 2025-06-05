<?php

namespace Rift\Core\Database\Configurators;

use Rift\Core\Contracts\Operation;
use Rift\Core\Contracts\OperationOutcome;
use Rift\Core\Database\Connect;

abstract class TenantConfigurator extends Operation
{
    protected static array $models = [];
    protected static string $tenantId;

    public static function forTenant(string $tenantId): self
    {
        static::$tenantId = $tenantId;
        return new static();
    }

    public static function configure(): OperationOutcome
    {
        if (static::$tenantId === 'system') {
            return self::error(self::HTTP_BAD_REQUEST, 'The "system" scheme is reserved by Rift');
        }

        $schema = 'tenant_' . static::$tenantId;

        // 1. Получаем admin подключение
        $adminPdoRequest = Connect::adminPdo();
        if ($adminPdoRequest->code !== self::HTTP_OK) {
            return $adminPdoRequest;
        }
        $adminPdo = $adminPdoRequest->result;

        // 2. Создаем схему или БД для тенанта
        try {
            if ($_ENV['DB_DRIVER'] === 'pgsql') {
                $adminPdo->exec("CREATE SCHEMA IF NOT EXISTS {$schema}");
            } elseif ($_ENV['DB_DRIVER'] === 'mysql') {
                $adminPdo->exec("CREATE DATABASE IF NOT EXISTS {$_ENV['DB_NAME']}_{$schema}");
            }
        } catch (\PDOException $e) {
            return self::error(500, "Failed to create tenant schema", [
                'tenant' => static::$tenantId,
                'error' => $e->getMessage()
            ]);
        }

        // 3. Подключаемся к схеме/базе тенанта
        $tenantPdoRequest = Connect::getPdoForSchema($schema);
        if ($tenantPdoRequest->code !== self::HTTP_OK) {
            return $tenantPdoRequest;
        }
        $tenantPdo = $tenantPdoRequest->result;

        // 4. Создаем таблицы моделей
        foreach (static::$models as $model) {
            try {
                $tenantPdo->exec($model::getMigrationSQL($schema));
            } catch (\PDOException $e) {
                return self::error(self::HTTP_INTERNAL_SERVER_ERROR, "Failed to create table for model: " . $model, [
                    'tenant' => static::$tenantId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return self::success("Tenant " . static::$tenantId . " configured successfully");
    }
}
