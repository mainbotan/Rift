<?php

namespace Rift\Contracts\Middlewares;

use Rift\Core\DataBus\OperationOutcome;
use Rift\Core\Http\Request;

interface MiddlewareInterface {
    public function execute(Request $request): OperationOutcome;
}