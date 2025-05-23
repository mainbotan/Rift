<?php

namespace Rift\Core\Models;

use Rift\Core\Contracts\Response;
use Rift\Core\Contracts\ResponseDTO;
use Rift\Core\Validators\Utils\SchemaValidator;

abstract class AbstractModel extends Response {
    // 1. Определение структуры таблицы и валидации
    abstract public static function getSchema(): array;
    
    // 2. Имя таблицы (можно переопределить)
    public static function getTableName(): string {
        return strtolower((new \ReflectionClass(static::class))->getShortName());
    }
    
    // 3. Валидация данных через Rift Validator
    public static function validate(array $data): ResponseDTO {
        return SchemaValidator::validate(static::getSchema(), $data);
    }
    
    // 4. Валидация отдельных полей
    public static function validateField(string $field, $value): ResponseDTO {
        $schema = static::getSchema();
        if (!isset($schema[$field])) {
            return self::error(self::HTTP_BAD_REQUEST, "Field {$field} not defined in model");
        }
        
        return SchemaValidator::validate([$field => $schema[$field]], [$field => $value]);
    }
    
    // 5. Генерация SQL для миграции
    public static function getMigrationSQL(string $schema = 'public'): string {
        $columns = [];
        foreach (static::getSchema() as $name => $rules) {
            $columns[] = "{$name} {$rules['db_type']}";
        }
        
        return sprintf(
            "CREATE TABLE IF NOT EXISTS %s (%s);",
            static::getTableName(),
            implode(', ', $columns)
        );
    }
}