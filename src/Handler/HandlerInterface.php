<?php

/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Interface for the action handler. 
 * The universal input point for processing the request.
 * |
 * |--------------------------------------------------------------------------
 */

namespace Rift\Core\Handler;

use Rift\Core\Contracts\OperationOutcome;

interface HandlerInterface {
    public function execute(array $data): OperationOutcome;
}