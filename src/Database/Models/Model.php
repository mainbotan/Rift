<?php

namespace Rift\Core\Database\Models;

use Rift\Contracts\Database\Models\ModelInterface;
use Rift\Core\Databus\Operation;
use Rift\Core\Databus\OperationOutcome;
use Rift\Validator\SchemaValidator;

abstract class Model implements ModelInterface 
{
    public Table $table;
    
    const NAME = '';
    const VERSION = '1.0.0';

    public function __construct() {
        $this->table = new Table(static::NAME, static::VERSION);
        $this->schema();
    }

    abstract protected function schema(): void;

    // ***** VALIDATION *****
    
    public function validate(array $data): OperationOutcome {
        $rules = [];
        foreach ($this->table->fields as $field) {
            if (isset($field['validation'])) {
                $rules[$field['name']] = $field['validation'];
            }
        }
        return SchemaValidator::validate($rules, $data);
    }

    public function validateField(string $fieldName, mixed $value): OperationOutcome {
        if (!isset($this->table->fields[$fieldName]['validation'])) {
            return Operation::success(null);
        }
        return SchemaValidator::validate(
            [$fieldName => $this->table->fields[$fieldName]['validation']], 
            [$fieldName => $value]
        );
    }

    // ***** MIGRATION *****
    
    public function migrate(): string {
        $commands = [
            $this->generateCreateTableSQL(),
            $this->generateAlterTableSQL(),
            $this->generateIndexesSQL(),
            $this->generateForeignKeysSQL()
        ];
        
        $commands = array_filter($commands);
        
        if (empty($commands)) {
            return '';
        }
        
        return implode(";\n", $commands);
    }

    private function generateCreateTableSQL(): string {
        $fields = [];
        
        foreach ($this->table->fields as $field) {
            $fieldDef = "{$field['name']} {$field['db_type']}";
            
            if (isset($field['nullable']) && !$field['nullable']) {
                $fieldDef .= ' NOT NULL';
            }
            
            if (isset($field['default'])) {
                $fieldDef .= ' DEFAULT ' . $this->formatDefaultValue($field['default']);
            }
            
            $fields[] = $fieldDef;
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
            if (!isset($field['action']) || $field['action'] === 'CREATE') {
                continue;
            }
            
            if ($field['action'] === 'UPDATE') {
                $alters[] = sprintf(
                    "DO $$\nBEGIN\n" .
                    "  IF EXISTS (SELECT 1 FROM information_schema.columns " .
                    "             WHERE table_name = '%s' AND column_name = '%s') THEN\n" .
                    "    EXECUTE format('ALTER TABLE %%I ALTER COLUMN %%I TYPE %s', '%s', '%s');\n" .
                    "  END IF;\n" .
                    "END\n$$;",
                    static::NAME,
                    $field['name'],
                    $field['db_type'],
                    static::NAME,
                    $field['name']
                );
            } 
            elseif ($field['action'] === 'DELETE') {
                $alters[] = sprintf(
                    "DO $$\nBEGIN\n" .
                    "  IF EXISTS (SELECT 1 FROM information_schema.columns " .
                    "             WHERE table_name = '%s' AND column_name = '%s') THEN\n" .
                    "    EXECUTE format('ALTER TABLE %%I DROP COLUMN %%I', '%s', '%s');\n" .
                    "  END IF;\n" .
                    "END\n$$;",
                    static::NAME,
                    $field['name'],
                    static::NAME,
                    $field['name']
                );
            }
        }
        
        return implode(";\n", $alters);
    }

    private function generateIndexesSQL(): string {
        $indexes = [];
        
        foreach ($this->table->indexes as $index) {
            $type = $index['unique'] ? 'UNIQUE INDEX' : 'INDEX';
            $columns = implode(', ', $index['columns']);
            
            $indexes[] = sprintf(
                "DO $$\nBEGIN\n" .
                "  IF NOT EXISTS (SELECT 1 FROM pg_indexes " .
                "                WHERE tablename = '%s' AND indexname = '%s') THEN\n" .
                "    EXECUTE format('CREATE %s IF NOT EXISTS %s ON %%I (%s)', '%s');\n" .
                "  END IF;\n" .
                "END\n$$;",
                static::NAME,
                $index['name'],
                $type,
                $index['name'],
                $columns,
                static::NAME
            );
        }
        
        return implode(";\n", $indexes);
    }

    private function generateForeignKeysSQL(): string {
        $fks = [];
        
        foreach ($this->table->foreignKeys as $fk) {
            $fks[] = sprintf(
                "DO $$\nBEGIN\n" .
                "  IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints " .
                "                WHERE constraint_name = '%s' AND table_name = '%s') THEN\n" .
                "    EXECUTE format('ALTER TABLE %%I ADD CONSTRAINT %s FOREIGN KEY (%s) " .
                "                   REFERENCES %s (%s) ON DELETE %s', '%s');\n" .
                "  END IF;\n" .
                "END\n$$;",
                $fk['name'],
                static::NAME,
                $fk['name'],
                $fk['column'],
                $fk['reference_table'],
                $fk['reference_column'],
                $fk['on_delete'] ?? 'NO ACTION',
                static::NAME
            );
        }
        
        return implode(";\n", $fks);
    }

    private function formatDefaultValue($value): string {
        if (is_string($value)) {
            // Простое экранирование кавычек для SQL
            return "'" . str_replace("'", "''", $value) . "'";
        }
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        if (is_null($value)) {
            return 'NULL';
        }
        if (is_array($value) || is_object($value)) {
            // Экранируем JSON-строку
            $json = json_encode($value);
            return "'" . str_replace("'", "''", $json) . "'";
        }
        return (string)$value;
    }
}