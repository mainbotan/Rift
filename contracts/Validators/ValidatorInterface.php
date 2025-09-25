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

use Rift\Core\Databus\ResultType;

interface ValidatorInterface {
    /**
     * validate public method
     * @return ResultType
     */
    public function validate(array $data): ResultType;
}