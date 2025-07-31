<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Middleware interface.
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Contracts\Middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Rift\Core\Databus\ResultType;

interface MiddlewareInterface {
    public function execute(ServerRequestInterface $request): ResultType;
}