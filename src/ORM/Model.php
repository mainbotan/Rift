<?php

namespace Rift\Core\ORM;

abstract class Model {
    public Table $table;
    
    const NAME = '';
    const VERSION = '1.0.0';

    public function __construct() {
        $this->table = new Table(static::NAME, static::VERSION);
        $this->schema();
    }

    abstract protected function schema(): void;

    public function migrate(): string {
        $commands = [];
        
        // Создание таблицы
        $createTable = $this->generateCreateTableSQL();
        if ($createTable) {
            $commands[] = $createTable;
        }
        
        // Изменение таблицы
        $alterTable = $this->generateAlterTableSQL();
        if ($alterTable) {
            $commands[] = $alterTable;
        }
        
        // Индексы
        $indexes = $this->generateIndexesSQL();
        if ($indexes) {
            $commands[] = $indexes;
        }
        
        // Внешние ключи
        $fks = $this->generateForeignKeysSQL();
        if ($fks) {
            $commands[] = $fks;
        }
        
        if (empty($commands)) {
            return '';
        }
        
        // Объединяем команды, добавляя ; после каждой
        $sqlBody = implode(";\n", $commands);
        
        return "BEGIN;\n" . $sqlBody . ";\nCOMMIT;";
    }

    private function generateCreateTableSQL(): string {
        $fields = [];
        
        foreach ($this->table->fields as $field) {
            if ($field['action'] === 'CREATE') {
                $fieldDef = "{$field['name']} {$field['db_type']}";
                
                if (isset($field['nullable']) && !$field['nullable']) {
                    $fieldDef .= ' NOT NULL';
                }
                
                if (isset($field['default'])) {
                    $fieldDef .= ' DEFAULT ' . $this->formatValue($field['default']);
                }
                
                $fields[] = $fieldDef;
            }
        }
        
        if (empty($fields)) {
            return '';
        }
        
        return "CREATE TABLE IF NOT EXISTS " . static::NAME . " (\n    " . 
            implode(",\n    ", $fields) . "\n)";
    }

    private function generateAlterTableSQL(): string {
        $alters = [];
        
        foreach ($this->table->fields as $field) {
            if ($field['action'] === 'UPDATE') {
                $alters[] = "ALTER TABLE " . static::NAME . " ALTER COLUMN " . 
                            $field['name'] . " TYPE " . $field['db_type'];
            } 
            elseif ($field['action'] === 'DELETE') {
                $alters[] = "ALTER TABLE " . static::NAME . " DROP COLUMN " . $field['name'];
            }
        }
        
        return empty($alters) ? '' : implode(";\n", $alters);
    }

    private function generateIndexesSQL(): string {
        $indexes = [];
        
        foreach ($this->table->indexes as $index) {
            $type = $index['unique'] ? 'UNIQUE INDEX' : 'INDEX';
            $indexes[] = "CREATE $type IF NOT EXISTS {$index['name']} ON " . 
                        static::NAME . " (" . implode(', ', $index['columns']) . ")";
        }
        
        return implode(";\n", $indexes);
    }

    private function generateForeignKeysSQL(): string {
        $fks = [];
        
        foreach ($this->table->foreignKeys as $fk) {
            $fks[] = "ALTER TABLE " . static::NAME . " ADD CONSTRAINT {$fk['name']} " .
                     "FOREIGN KEY ({$fk['column']}) REFERENCES {$fk['reference_table']} " .
                     "({$fk['reference_column']}) ON DELETE " . ($fk['on_delete'] ?? 'NO ACTION');
        }
        
        return empty($fks) ? '' : implode(";\n", $fks);
    }

    private function formatValue($value): string {
        if (is_string($value)) {
            return "'" . str_replace("'", "''", $value) . "'";
        }
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        if (is_null($value)) {
            return 'NULL';
        }
        if (is_array($value) || is_object($value)) {
            return "'" . json_encode($value) . "'";
        }
        return (string)$value;
    }
}