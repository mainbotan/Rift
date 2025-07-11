<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * ORM part -> model.
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Core\ORM;

use Rift\Core\ORM\SchemaBuilder;
use Rift\Core\Databus\OperationOutcome;
use Rift\Core\Models\Model as BaseModel;

abstract class Model extends BaseModel
{
    private static array $schemaDefinition = [];
    private static array $indexes = [];
    private static array $foreignKeys = [];
    private static array $relations = [];
    private static array $triggers = [];
    private static array $hooks = [
        'beforeCreate' => [],
        'afterCreate' => [],
        'beforeUpdate' => [],
        'afterUpdate' => [],
        'beforeDelete' => [],
        'afterDelete' => []
    ];

    public static function define(): SchemaBuilder
    {
        return new SchemaBuilder(static::class);
    }

    public static function getSchema(): array
    {
        return self::$schemaDefinition;
    }

    public static function getIndexes(): array
    {
        return self::$indexes;
    }

    public static function getForeignKeys(): array
    {
        return self::$foreignKeys;
    }

    public static function getRelations(): array
    {
        return self::$relations;
    }

    public static function getTriggers(): array
    {
        return self::$triggers;
    }

    public static function getHooks(string $type): array
    {
        return self::$hooks[$type] ?? [];
    }

    public static function addIndex(array $index): void
    {
        self::$indexes[] = $index;
    }

    public static function addForeignKey(array $foreignKey): void
    {
        self::$foreignKeys[] = $foreignKey;
    }

    public static function setSchema(array $schema): void
    {
        self::$schemaDefinition = $schema;
    }

    public static function addRelation(array $relation): void
    {
        self::$relations[] = $relation;
    }

    public static function addTrigger(array $trigger): void
    {
        self::$triggers[] = $trigger;
    }

    public static function addHook(string $type, callable $callback): void
    {
        self::$hooks[$type][] = $callback;
    }
}
