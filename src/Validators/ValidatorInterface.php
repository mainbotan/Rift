<?php

namespace Rift\Core\Validators;

use Rift\Core\Contracts\ResponseDTO;

// Гарантирует контракт у всех валидаторов

interface ValidatorInterface {
    public function execute(array $data): ResponseDTO;
}