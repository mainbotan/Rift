<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Interface for the action handler (UseCase). 
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Contracts\Handlers;

use Rift\Core\Databus\OperationOutcome;

interface HandlerInterface {
    public function execute(array $data): OperationOutcome;
}