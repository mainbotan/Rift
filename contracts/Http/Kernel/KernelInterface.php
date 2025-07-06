<?php

namespace Rift\Contracts\Http\Kernel;

use Rift\Contracts\Http\Request\RequestInterface;
use Rift\Core\Databus\OperationOutcome;

interface KernelInterface {
    /**
     * handle public method
     * processing request
     * @return OperationOutcome
     */
    public function handle(RequestInterface $request): OperationOutcome;
}