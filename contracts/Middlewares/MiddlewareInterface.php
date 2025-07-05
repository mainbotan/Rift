<?php

namespace Rift\Contracts\Middlewares;

use Rift\Core\Databus\OperationOutcome;
use Rift\Core\Http\Request\Request;

interface MiddlewareInterface {
    public function execute(Request $request): OperationOutcome;
}