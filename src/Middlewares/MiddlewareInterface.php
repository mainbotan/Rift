<?php

namespace Rift\Core\Middlewares;

use Rift\Core\Contracts\OperationOutcome;
use Rift\Core\Http\Request;

interface MiddlewareInterface {
    public function execute(Request $request): OperationOutcome;
}