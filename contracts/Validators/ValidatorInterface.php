<?php

namespace Rift\Contracts\Validators;

use Rift\Core\Databus\OperationOutcome;

interface ValidatorInterface {
    /**
     * validate public method
     * @return OperationOutcome
     */
    public function validate(array $data): OperationOutcome;
}