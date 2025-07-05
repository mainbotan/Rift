<?php

namespace Rift\Contracts\Http\Router;

use Rift\Core\Databus\OperationOutcome;
use DI\Container;
use Rift\Contracts\Http\RoutesBox\RoutesBoxInterface;
use Rift\Contracts\Http\Request\RequestInterface;

interface RouterInterface {
    /**
     * construct router
     */
    public function __construct(RoutesBoxInterface $routesBox, Container $container);

    /**
     * entrypoint of processing request
     * @return OperationOutcome
     */
    public function execute(RequestInterface $request): OperationOutcome;
}