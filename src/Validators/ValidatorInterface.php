<?php

namespace Rift\Core\Validators;

use Rift\Core\Contracts\OperationOutcome;

// Гарантирует контракт у всех валидаторов

interface ValidatorInterface {
    public function execute(array $data): OperationOutcome;
}