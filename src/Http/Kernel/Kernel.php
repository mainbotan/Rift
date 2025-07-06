<?php

namespace Rift\Core\Http\Kernel\Kernel;

use Rift\Contracts\Http\Kernel\KernelInterface;
use Rift\Contracts\Http\Request\RequestInterface;
use Rift\Contracts\Http\ResponseEmitter\EmitterInterface;
use Rift\Contracts\Http\Router\RouterInterface;
use Rift\Core\Databus\Operation;

class Kernel implements KernelInterface {
    /**
     * Construct kernel
     */
    public function __construct(
        private RouterInterface $router,
        private EmitterInterface $emitter
    ) { }

    /**
     * Starting the request processing process
     * @param RequestInterface
     * @return OperationOutcome
     */
    public function handle(RequestInterface $request): OperationOutcome {
        return Operation::success(null);
    }
}