<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Http Kernel interface.
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Contracts\Http\Kernel;

use Psr\Http\Message\ServerRequestInterface;
use Rift\Core\Databus\OperationOutcome;

interface KernelInterface {
    /**
     * handle public method
     * processing request
     * @return OperationOutcome
     */
    public function handle(ServerRequestInterface $request): OperationOutcome;
}