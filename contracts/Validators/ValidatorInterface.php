<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Validator interface.
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Contracts\Validators;

use Rift\Core\Databus\OperationOutcome;

interface ValidatorInterface {
    /**
     * validate public method
     * @return OperationOutcome
     */
    public function validate(array $data): OperationOutcome;
}