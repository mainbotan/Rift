<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * An abstract model of an entity + methods for processing it.
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Core\Models;

use Rift\Contracts\Models\ModelInterface;
use Rift\Core\Databus\Operation;
use Rift\Core\Databus\OperationOutcome;
use Rift\Validator\SchemaValidator;

abstract class Model implements ModelInterface
{
    abstract public static function getSchema(): array;

    public static function getIndexes(): array
    {
        return [];
    }
    
    public static function getForeignKeys(): array
    {
        return [];
    }
    
    public static function getVersion(): string {
        return '1.0.0';
    }
    
    public static function getTableName(): string
    {
        return strtolower((new \ReflectionClass(static::class))->getShortName());
    }
    
    public static function validate(array $data): OperationOutcome
    {
        return SchemaValidator::validate(static::getSchema(), $data);
    }
    
    public static function validateField(string $field, $value): OperationOutcome
    {
        $schema = static::getSchema();
        if (!isset($schema[$field])) {
            return Operation::error(Operation::HTTP_BAD_REQUEST, "Field {$field} not defined in model");
        }
        
        return SchemaValidator::validate([$field => $schema[$field]], [$field => $value]);
    }
    
    public static function getMigrationSQL(): string
    {
        $sql = [];
        
        // Создание таблицы
        $columns = [];
        foreach (static::getSchema() as $name => $rules) {
            $columns[] = static::generateColumnSQL($name, $rules);
        }
        
        $sql[] = sprintf(
            "CREATE TABLE IF NOT EXISTS %s (%s);",
            static::getTableName(),
            implode(', ', $columns)
        );
        
        // Добавление индексов
        foreach (static::getIndexes() as $index) {
            $sql[] = static::generateIndexSQL($index);
        }
        
        // Добавление внешних ключей
        foreach (static::getForeignKeys() as $fk) {
            $fkSql = static::generateForeignKeySQL($fk);
            if (!empty($fkSql)) {
                $sql[] = $fkSql;
            }
        }
        
        return implode("\n", $sql);
    }
    public static function getAlterTableSQL(): string
    {
        $sql = [];
        $tableName = static::getTableName();

        // Добавление новых колонок
        foreach (static::getSchema() as $name => $rules) {
            $columnDefinition = static::generateColumnSQL($name, $rules);
            $sql[] = sprintf(
                "ALTER TABLE %s ADD COLUMN IF NOT EXISTS %s;",
                $tableName,
                $columnDefinition
            );
        }

        // Создание отсутствующих индексов
        foreach (static::getIndexes() as $index) {
            $indexName = $index['name'] ?? 'idx_'.$tableName.'_'.implode('_', $index['columns']);
            $sql[] = sprintf(
                "CREATE %s INDEX IF NOT EXISTS %s ON %s (%s);",
                !empty($index['unique']) ? 'UNIQUE' : '',
                $indexName,
                $tableName,
                implode(', ', $index['columns'])
            );
        }

        // Добавление внешних ключей (только если не существуют)
        foreach (static::getForeignKeys() as $fk) {
            $fkName = $fk['name'] ?? 'fk_'.$tableName.'_'.$fk['column'];
            $sql[] = sprintf(
                "ALTER TABLE %s ADD CONSTRAINT IF NOT EXISTS %s FOREIGN KEY (%s) REFERENCES %s(%s)%s;",
                $tableName,
                $fkName,
                $fk['column'],
                $fk['reference_table'],
                $fk['reference_column'],
                !empty($fk['on_delete']) ? " ON DELETE {$fk['on_delete']}" : ''
            );
        }

        return implode("\n", $sql);
    }

    protected static function generateColumnSQL(string $name, array $rules): string {
        if (!isset($rules['db_type'])) {
            throw new \RuntimeException("Missing db_type for field {$name}");
        }
        return "{$name} {$rules['db_type']}";
    }

    protected static function generateIndexSQL(array $index): string
    {
        $name = $index['name'] ?? 'idx_'.static::getTableName().'_'.implode('_', $index['columns']);
        $unique = !empty($index['unique']) ? 'UNIQUE' : '';
        
        return sprintf(
            "CREATE %s INDEX %s ON %s (%s);",
            $unique,
            $name,
            static::getTableName(),
            implode(', ', $index['columns'])
        );
    }
    
    protected static function generateForeignKeySQL(array $fk): ?string
    {
        if ($_ENV['DB_DRIVER'] === 'mysql' && empty($fk['skip_index'])) {
            $indexName = $fk['index_name'] ?? 'idx_'.static::getTableName().'_'.$fk['column'];
            $indexSql = sprintf(
                "CREATE INDEX %s ON %s (%s);",
                $indexName,
                static::getTableName(),
                $fk['column']
            );
        }
        
        $onDelete = !empty($fk['on_delete']) ? " ON DELETE {$fk['on_delete']}" : '';
        
        $sql = sprintf(
            "ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s(%s)%s;",
            static::getTableName(),
            $fk['name'] ?? 'fk_'.static::getTableName().'_'.$fk['column'],
            $fk['column'],
            $fk['reference_table'],
            $fk['reference_column'],
            $onDelete
        );
        
        return ($indexSql ?? '').$sql;
    }
    
    public static function validateModel(): OperationOutcome
    {
        foreach (static::getForeignKeys() as $fk) {
            if (empty($fk['reference_table'])) {
                return Operation::error(Operation::HTTP_INTERNAL_SERVER_ERROR, "Missing reference_table in foreign key");
            }
        }
        
        return Operation::success(null);
    }
}