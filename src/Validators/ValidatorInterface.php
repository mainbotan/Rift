<?php

/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * The validator interface.
 * |
 * |--------------------------------------------------------------------------
 */


namespace Rift\Core\Validators;

use Rift\Core\DataBus\OperationOutcome;

interface ValidatorInterface {
    public function execute(array $data): OperationOutcome;
}