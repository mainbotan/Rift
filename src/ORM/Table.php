<?php

namespace Rift\Core\ORM;

final class Table
{
    public array $fields = [];
    public array $indexes = [];
    public array $foreignKeys = [];
    public array $tableOptions = [];
    
    private array $currentField = [];
    private array $currentIndex = [];
    private array $currentForeignKey = [];

    // current option: FIELD | INDEX | KEY | TABLE
    private string|null $currentOption = null;
    const FIELD_OPTION_NAME = 'FIELD';
    const INDEX_OPTION_NAME = 'INDEX';
    const KEY_OPTION_NAME = 'KEY';

    public function __construct(
        public string $name,
        public string|int $version = '1.0.0'
    ) { }

    // Validation
    public function validation(array $validationRules): self {
        $this->currentField['validation'] = $validationRules;
        return $this;
    }

    // Field operations
    public function create(string $fieldName): self {
        return $this->fieldAction($fieldName, 'CREATE');
    }
    
    public function update(string $fieldName): self {
        return $this->fieldAction($fieldName, 'UPDATE');
    }
    
    public function delete(string $fieldName): self {
        return $this->fieldAction($fieldName, 'DELETE');
    }
    
    private function fieldAction(string $fieldName, string $action): self {
        $this->currentField = [
            'name' => $fieldName,
            'action' => $action
        ];
        $this->currentOption = self::FIELD_OPTION_NAME;
        return $this;
    }

    // Field properties
    public function type(string $dbType): self {
        $this->currentField['db_type'] = $dbType;
        return $this;
    }
    
    public function comment(string $comment): self {
        $this->currentField['comment'] = $comment;
        return $this;
    }
    
    public function defaultValue($value): self {
        $this->currentField['default'] = $value;
        return $this;
    }
    
    public function nullable(bool $nullable = true): self {
        $this->currentField['nullable'] = $nullable;
        return $this;
    }

    // Index operations
    public function addIndex(array $columns, string $name = null): self {
        $this->currentIndex = [
            'columns' => $columns,
            'name' => $name ?? 'idx_'.$this->name.'_'.implode('_', $columns),
            'unique' => false
        ];
        $this->currentOption = self::INDEX_OPTION_NAME;
        return $this;
    }
    
    public function uniqueIndex(array $columns, string $name = null): self {
        $this->currentIndex = [
            'columns' => $columns,
            'name' => $name ?? 'uidx_'.$this->name.'_'.implode('_', $columns),
            'unique' => true
        ];
        $this->currentOption = self::INDEX_OPTION_NAME;
        return $this;
    }

    // Foreign key operations
    public function addForeignKey(
        string $column, 
        string $referenceTable, 
        string $referenceColumn,
        string $name = null
    ): self {
        $this->currentForeignKey = [
            'column' => $column,
            'reference_table' => $referenceTable,
            'reference_column' => $referenceColumn,
            'name' => $name ?? 'fk_'.$this->name.'_'.$column,
            'on_delete' => null,
            'on_update' => null
        ];
        $this->currentOption = self::KEY_OPTION_NAME;
        return $this;
    }
    
    public function onDelete(string $action): self {
        $this->currentForeignKey['on_delete'] = $action;
        return $this;
    }
    
    public function onUpdate(string $action): self {
        $this->currentForeignKey['on_update'] = $action;
        return $this;
    }

    // Table options
    public function engine(string $engine): self {
        $this->tableOptions['engine'] = $engine;
        return $this;
    }
    
    public function charset(string $charset): self {
        $this->tableOptions['charset'] = $charset;
        return $this;
    }
    
    public function collation(string $collation): self {
        $this->tableOptions['collation'] = $collation;
        return $this;
    }
    
    public function commentTable(string $comment): self {
        $this->tableOptions['comment'] = $comment;
        return $this;
    }

    // Finalization methods
    private function affirmField(): self {
        if (empty($this->currentField)) {
            throw new \RuntimeException('No field configuration to affirm');
        }
        
        $this->fields[$this->currentField['name']] = $this->currentField;
        $this->currentField = [];
        return $this;
    }
    
    private function affirmIndex(): self {
        if (empty($this->currentIndex)) {
            throw new \RuntimeException('No index configuration to affirm');
        }
        
        $this->indexes[] = $this->currentIndex;
        $this->currentIndex = [];
        return $this;
    }
    
    private function affirmForeignKey(): self {
        if (empty($this->currentForeignKey)) {
            throw new \RuntimeException('No foreign key configuration to affirm');
        }
        
        $this->foreignKeys[] = $this->currentForeignKey;
        $this->currentForeignKey = [];
        return $this;
    }

    // Alias for backward compatibility
    public function affirm(): void {
        switch ($this->currentOption) {
            case self::FIELD_OPTION_NAME:
                $this->affirmField();
                break;
            case self::INDEX_OPTION_NAME:
                $this->affirmIndex();
                break;
            case self::KEY_OPTION_NAME:
                $this->affirmForeignKey();
                break;      
        }
        $this->currentOption = null;
    }
}