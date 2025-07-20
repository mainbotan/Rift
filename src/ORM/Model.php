<?php

namespace Rift\Core\ORM;

use Rift\Contracts\ORM\ModelInterface;

abstract class Model implements ModelInterface {
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
        
        // Для новых полей используем ALTER TABLE ADD COLUMN
        $newFields = $this->getNewFieldsSQL();
        if ($newFields) {
            $commands[] = $newFields;
        }
        
        // Остальные операции (индексы, внешние ключи)
        $commands[] = $this->generateCreateTableSQL(); // Основная таблица
        $commands[] = $this->generateAlterTableSQL();  // Изменения полей
        $commands[] = $this->generateIndexesSQL();     // Индексы
        $commands[] = $this->generateForeignKeysSQL(); // Внешние ключи
        
        $commands = array_filter($commands);
        
        if (empty($commands)) {
            return '';
        }
        
        return "BEGIN;\n" . implode(";\n", $commands) . ";\nCOMMIT;";
    }

    private function getNewFieldsSQL(): string {
        $alters = [];
        
        foreach ($this->table->fields as $field) {
            if ($field['action'] === 'CREATE') {
                $fieldDef = "{$field['name']} {$field['db_type']}";
                
                if (isset($field['nullable']) && !$field['nullable']) {
                    $fieldDef .= ' NOT NULL';
                }
                
                if (isset($field['default'])) {
                    // Для строковых значений используем двойные кавычки внутри format()
                    $default = $this->formatValue($field['default']);
                    $fieldDef .= " DEFAULT " . str_replace("'", "''", $default);
                }
                
                $alters[] = sprintf(
                    "DO $$\nBEGIN\n" .
                    "  IF NOT EXISTS (SELECT 1 FROM information_schema.columns " .
                    "                WHERE table_name = '%s' AND column_name = '%s') THEN\n" .
                    "    EXECUTE format('ALTER TABLE %%I ADD COLUMN %s', '%s');\n" .
                    "  END IF;\n" .
                    "END\n$$;",
                    static::NAME,
                    $field['name'],
                    $fieldDef,
                    static::NAME
                );
            }
        }
        
        return implode("\n", $alters);
    }

    private function generateCreateTableSQL(): string {
        $fields = [];
        
        foreach ($this->table->fields as $field) {
            // Только поля без явного действия или с другими действиями
            if (!isset($field['action']) || $field['action'] !== 'CREATE') {
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
            // Возвращаем значение без внешних кавычек
            return $value;
        }
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        if (is_null($value)) {
            return 'NULL';
        }
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        return (string)$value;
    }
}