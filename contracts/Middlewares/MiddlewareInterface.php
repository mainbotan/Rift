<?php

namespace Rift\Contracts\Middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Rift\Core\Databus\OperationOutcome;

interface MiddlewareInterface {
    public function execute(ServerRequestInterface $request): OperationOutcome;
}