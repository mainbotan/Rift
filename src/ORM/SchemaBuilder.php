<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * ORM part -> schema builder.
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Core\ORM;

class SchemaBuilder
{
    private string $modelClass;
    private ?string $currentField;
    private array $currentOptions = [];

    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
    }

    /**********************
     * FIELD DEFINITION
     *********************/
    public function field(string $name): self
    {
        // Автоматически применяем предыдущее поле
        if ($this->currentField !== null && !empty($this->currentOptions)) {
            $this->apply();
        }
        
        $this->currentField = $name;
        $this->currentOptions = ['type' => 'mixed'];
        return $this;
    }

    public function type(string $type): self
    {
        $this->currentOptions['type'] = $type;
        return $this;
    }

    public function dbType(string $dbType): self
    {
        $this->currentOptions['db_type'] = $dbType;
        return $this;
    }

    /**********************
     * FIELD CONSTRAINTS
     *********************/
    public function notNull(): self
    {
        $this->currentOptions['required'] = true;
        $this->appendToDbType('NOT NULL');
        return $this;
    }

    public function unique(): self
    {
        $this->appendToDbType('UNIQUE');
        return $this;
    }

    public function primaryKey(): self
    {
        $this->appendToDbType('PRIMARY KEY');
        return $this;
    }

    public function default($value): self
    {
        $this->currentOptions['default'] = $value;
        $defaultValue = is_string($value) ? "'$value'" : $value;
        $this->appendToDbType("DEFAULT $defaultValue");
        return $this;
    }

    /**********************
     * FIELD VALIDATION
     *********************/
    public function validate(callable $validator, string $message = 'Validation failed'): self
    {
        $this->currentOptions['validate'] = $validator;
        $this->currentOptions['message'] = $message;
        return $this;
    }

    public function length(int $min, ?int $max = null): self
    {
        $this->currentOptions['min'] = $min;
        if ($max !== null) {
            $this->currentOptions['max'] = $max;
        }
        return $this;
    }

    public function range($min, $max): self
    {
        $this->currentOptions['min'] = $min;
        $this->currentOptions['max'] = $max;
        return $this;
    }

    public function enum(array $values): self
    {
        $this->currentOptions['enum'] = $values;
        return $this;
    }

    /**********************
     * SCHEMA STRUCTURE
     *********************/
    public function index(array $columns, bool $unique = false, ?string $name = null): self
    {
        $this->modelClass::addIndex([
            'columns' => $columns,
            'unique' => $unique,
            'name' => $name ?? 'idx_' . implode('_', $columns)
        ]);
        return $this;
    }

    public function foreignKey(
        string $column,
        string $referenceTable,
        string $referenceColumn,
        ?string $onDelete = null,
        ?string $onUpdate = null,
        ?string $name = null
    ): self {
        $this->modelClass::addForeignKey([
            'column' => $column,
            'reference_table' => $referenceTable,
            'reference_column' => $referenceColumn,
            'on_delete' => $onDelete,
            'on_update' => $onUpdate,
            'name' => $name ?? 'fk_' . $column
        ]);
        return $this;
    }

    /**********************
     * RELATIONSHIPS
     *********************/
    public function hasOne(string $relatedClass, ?string $foreignKey = null): self
    {
        $this->modelClass::addRelation([
            'type' => 'hasOne',
            'related' => $relatedClass,
            'foreignKey' => $foreignKey
        ]);
        return $this;
    }

    public function hasMany(string $relatedClass, ?string $foreignKey = null): self
    {
        $this->modelClass::addRelation([
            'type' => 'hasMany',
            'related' => $relatedClass,
            'foreignKey' => $foreignKey
        ]);
        return $this;
    }

    public function belongsTo(string $relatedClass, ?string $foreignKey = null): self
    {
        $this->modelClass::addRelation([
            'type' => 'belongsTo',
            'related' => $relatedClass,
            'foreignKey' => $foreignKey
        ]);
        return $this;
    }

    public function manyToMany(
        string $relatedClass,
        string $pivotTable,
        string $foreignKey,
        string $relatedKey
    ): self {
        $this->modelClass::addRelation([
            'type' => 'manyToMany',
            'related' => $relatedClass,
            'pivot_table' => $pivotTable,
            'foreign_key' => $foreignKey,
            'related_key' => $relatedKey
        ]);
        return $this;
    }

    /**********************
     * TRIGGERS & HOOKS
     *********************/
    public function trigger(string $event, string $sql): self
    {
        $this->modelClass::addTrigger([
            'event' => $event,
            'sql' => $sql
        ]);
        return $this;
    }

    public function beforeCreate(callable $callback): self
    {
        $this->modelClass::addHook('beforeCreate', $callback);
        return $this;
    }

    /**********************
     * COMPLEX TYPES
     *********************/
    public function json(): self
    {
        return $this->type('json')->dbType('JSON');
    }

    public function uuid(): self
    {
        return $this->type('string')
            ->dbType('UUID')
            ->length(36, 36)
            ->validate(
                fn($value) => preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value),
                'Invalid UUID format'
            );
    }

    public function timestamps(): self
    {
        $this->field('created_at')
            ->type('datetime')
            ->dbType('TIMESTAMP')
            ->default('CURRENT_TIMESTAMP')
            ->apply();

        $this->field('updated_at')
            ->type('datetime')
            ->dbType('TIMESTAMP')
            ->default('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')
            ->apply();

        return $this;
    }

    /**********************
     * UTILITY METHODS
     *********************/
    public function apply(): self
    {
        $schema = $this->modelClass::getSchema();
        $schema[$this->currentField] = $this->currentOptions;
        $this->modelClass::setSchema($schema);
        
        $this->currentField = null;
        $this->currentOptions = [];
        
        return $this;
    }

    private function appendToDbType(string $clause): void
    {
        if (isset($this->currentOptions['db_type'])) {
            if (!str_contains($this->currentOptions['db_type'], $clause)) {
                $this->currentOptions['db_type'] .= ' ' . $clause;
            }
        } else {
            $this->currentOptions['db_type'] = $clause;
        }
    }
}
