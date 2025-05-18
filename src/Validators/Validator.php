<?php

namespace Rift\Validators;

use Rift\Core\Contracts\Response;
use Rift\Core\Contracts\ResponseDTO;
use Rift\Core\Validators\ValidatorInterface;
use Rift\Core\Validators\Utils\SchemaValidator;

class Validator extends Response implements ValidatorInterface {
    // Схема запроса
    private array $schema = [
        'id' => [
            'type' => 'string',
            'min' => 3,
            'max' => 10
        ],
        'limit' => [
            'type' => 'int',
            'min' => 1,
            'max' => 100,
            'optional' => true
        ]
    ];

    public function execute(array $data): ResponseDTO {
        return SchemaValidator::validate($this->schema, $data);
    }
}