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

use Psr\Http\Message\ServerRequestInterface;
use Rift\Core\Databus\ResultType;

interface HandlerInterface {
    public function execute(ServerRequestInterface $request): ResultType;
}