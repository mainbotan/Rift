<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Http Kernel foundation.
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Core\Http\Kernel;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Rift\Core\Databus\Operation;
use Rift\Core\Databus\OperationOutcome;
use Rift\Contracts\Http\Kernel\KernelInterface;
use Rift\Contracts\Http\Request\RequestInterface;
use Rift\Contracts\Http\ResponseEmitter\EmitterInterface;
use Rift\Contracts\Http\Router\RouterInterface;

class Kernel implements KernelInterface {

    // DI container
    private ContainerInterface $container;

    /**
     * Construct kernel
     * @param array application di configuration
     */
    public function __construct(
        ContainerInterface $container
    ) { 
        $this->container = $container;
    }

    /**
     * Starting the request processing process
     * @param RequestInterface
     * @return OperationOutcome
     */
    public function handle(ServerRequestInterface $request): OperationOutcome {
        try {
            $result = $this->container->get(RouterInterface::class)->execute($request);
            
            if (!$result instanceof OperationOutcome) {
                $result = Operation::error(
                    Operation::HTTP_INTERNAL_SERVER_ERROR,
                    'Router returned invalid response type'
                );
            }
        } catch (\Exception $e) {
            $result = Operation::error(Operation::HTTP_INTERNAL_SERVER_ERROR, "The router is not registered in the di config: {$e->getMessage()}");
        } 
        $this->emit($result, $request);
        return $result;
    }   

    /**
     * Output of the execution result
     * @param OperationOutcome $result
     * @param RequestInterface $request
     * @return void
     */
    protected function emit(OperationOutcome $result, ServerRequestInterface $request): void {
        $this->container->get(EmitterInterface::class)->emit($result, $request);
    }
}