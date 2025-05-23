<?php

namespace Rift\Validators;

use Rift\Core\Contracts\ResponseDTO;
use Rift\Core\Validators\ValidatorInterface;
use Rift\Core\Validators\Utils\SchemaValidator;

class Validator implements ValidatorInterface {
    // Схема запроса
    private array $schema = [
        'name' => [
            'type' => 'string',
            'min' => 3,
            'max' => 64
        ],
        'email' => [
            'type' => 'string',
            'min' => 3,
            'max' => 64
        ],
        'password' => [
            'type' => 'string',
            'min' => 3,
            'max' => 64
        ]
    ];

    public function execute(array $data): ResponseDTO {
        return SchemaValidator::validate($this->schema, $data);
    }
}