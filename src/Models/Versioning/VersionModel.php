<?php

namespace Rift\Core\Models\Versioning;

use Rift\Core\Models\Model;

class VersionModel extends Model 
{
    public static function getTableName(): string 
    { 
        return 'versions'; 
    }

    public static function getSchema(): array
    {
        return [
            'table_name' => [
                'db_type' => 'VARCHAR(64) NOT NULL UNIQUE'
            ],
            'version' => [
                'db_type' => 'VARCHAR(20) NOT NULL'
            ],
            'updated_at' => [
                'db_type' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
            ]
        ];
    }

    public static function getIndexes(): array
    {
        return [
            [
                'columns' => ['table_name'],
                'unique' => true
            ]
        ];
    }
}