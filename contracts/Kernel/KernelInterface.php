<?php

namespace Rift\Contracts\Kernel;

use Rift\Contracts\Http\Request\RequestInterface;
use Rift\Core\Databus\OperationOutcome;

interface KernelInterface {
    public function handle(RequestInterface $request): OperationOutcome;
}