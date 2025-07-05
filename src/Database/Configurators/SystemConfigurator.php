<?php

/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Initialization of the system schema.
 * |
 * |--------------------------------------------------------------------------
 */

namespace Rift\Core\Database\Configurators;

use Rift\Core\Databus\Operation;
use Rift\Core\Databus\OperationOutcome;
use Rift\Core\Database\Connect;

abstract class SystemConfigurator extends Operation
{
    protected static array $models = [];

    public static function configure(): OperationOutcome
    {
        $schema = 'system'; // универсально

        // 1. Получаем admin подключение
        $adminPdoRequest = Connect::adminPdo();
        if ($adminPdoRequest->code !== self::HTTP_OK) {
            return $adminPdoRequest;
        }
        $adminPdo = $adminPdoRequest->result;

        // 2. Создаем схему (или БД) system
        try {
            if ($_ENV['DB_DRIVER'] === 'pgsql') {
                $adminPdo->exec("CREATE SCHEMA IF NOT EXISTS {$schema}");
            } elseif ($_ENV['DB_DRIVER'] === 'mysql') {
                $adminPdo->exec("CREATE DATABASE IF NOT EXISTS {$_ENV['DB_NAME']}_{$schema}");
            }
        } catch (\PDOException $e) {
            return self::error(500, "Failed to create system schema", [
                'error' => $e->getMessage()
            ]);
        }

        // 3. Подключаемся к системной схеме/базе
        $systemPdoRequest = Connect::getPdoForSchema($schema);
        if ($systemPdoRequest->code !== self::HTTP_OK) {
            return $systemPdoRequest;
        }
        $systemPdo = $systemPdoRequest->result;

        // 4. Создаем таблицы моделей
        foreach (static::$models as $model) {
            try {
                $systemPdo->exec($model::getMigrationSQL($schema));
            } catch (\PDOException $e) {
                return self::error(self::HTTP_INTERNAL_SERVER_ERROR, "Failed to create table for model: " . $model, [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return self::success("System configured successfully");
    }
}