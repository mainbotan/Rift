<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * RESTful router interface.
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Contracts\Http\Router;

use Rift\Core\Databus\OperationOutcome;
use DI\Container;
use Rift\Contracts\Http\RoutesBox\RoutesBoxInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RouterInterface {
    /**
     * Router configuration
     * 
     * @param RoutesBoxInterface $routesBox
     * @param Container $container
     */
    public function __construct(RoutesBoxInterface $routesBox, Container $container);

    /**
     * Entrypoint of processing request
     * 
     * @param ServerRequestInterface $request
     * @return OperationOutcome
     */
    public function execute(ServerRequestInterface $request): OperationOutcome;
}