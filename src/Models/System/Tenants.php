<?php

namespace Rift\Models\System;

use Rift\Core\Models\AbstractModel;

class Tenants extends AbstractModel {
    public static function getSchema(): array
    {
        return [
            'id' => [
                'type' => 'int',
                'db_type' => 'SERIAL PRIMARY KEY'
            ],
            'email' => [
                'type' => 'string',
                'min' => 5,
                'max' => 64,
                'required' => true,
                'db_type' => 'VARCHAR(64) NOT NULL UNIQUE',
                'message' => 'Invalid email format',
                'validate' => function($value) {
                    return filter_var($value, FILTER_VALIDATE_EMAIL);
                }
            ],
            'password' => [
                'type' => 'string',
                'min' => 8,
                'max' => 64,
                'required' => true,
                'db_type' => 'VARCHAR(64) NOT NULL'
            ],
            'status' => [
                'type' => 'string',
                'enum' => ['active', 'pending', 'banned'],
                'default' => 'pending',
                'db_type' => 'VARCHAR(20) DEFAULT \'pending\''
            ]
        ];
    }
}