<?php

namespace Rift\Models\System;

use Rift\Core\Models\AbstractModel;

class Plans extends AbstractModel {
    public static function getSchema(): array
    {
        return [
            'id' => [
                'type' => 'int',
                'db_type' => 'SERIAL PRIMARY KEY'
            ],
            'name' => [
                'type' => 'string',
                'enum' => ['basic', 'middle', 'pro'],
                'db_type' => 'VARCHAR(16) NOT NULL'
            ],
            'created_at' => [
                'type' => 'string',
                'db_type' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
            ],
            'updated_at' => [
                'type' => 'string',
                'db_type' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
            ]
        ];
    }
}