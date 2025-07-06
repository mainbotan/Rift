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
namespace Rift\Core\Http\Kernel\Kernel;

use Psr\Container\ContainerInterface;
use DI\ContainerBuilder;
use Psr\Http\Message\ServerRequestInterface;
use Rift\Core\Databus\Operation;
use Rift\Core\Databus\OperationOutcome;
use Rift\Contracts\Http\Kernel\KernelInterface;
use Rift\Contracts\Http\Request\RequestInterface;
use Rift\Contracts\Http\ResponseEmitter\EmitterInterface;
use Rift\Contracts\Http\Router\RouterInterface;

class Kernel implements KernelInterface {
    // DI Configuration
    private ?array $diConfiguration = null;

    // DI container
    private ?ContainerInterface $container = null;

    /**
     * Construct kernel
     * @param array application di configuration
     */
    public function __construct(
        array $diConfiguration
    ) { 
        $this->diConfiguration = $diConfiguration;
    }

    /**
     * Starting the request processing process
     * @param RequestInterface
     * @return OperationOutcome
     */
    public function handle(ServerRequestInterface $request): OperationOutcome {
        $container ??= $this->initContainer();
    }

    /**
     * Initializing the DI container
     * @return ContainerInterface
     */
    public function initContainer(): ContainerInterface {
        $builder = new ContainerBuilder();
        $builder->addDefinitions($this->diConfiguration);
        return $builder->build();
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