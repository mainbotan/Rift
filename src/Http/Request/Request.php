<?php

/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Initialization of the request object. PSR-7 compatibility.
 * |
 * |--------------------------------------------------------------------------
 */

namespace Rift\Core\Http\Request;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Rift\Core\Databus\Operation;
use Rift\Core\Databus\OperationOutcome;
use Psr\Http\Message\ServerRequestInterface;
use Rift\Contracts\Http\Request\RequestInterface;

class Request implements RequestInterface
{
    /**
     * Getting PSR-7 request object
     * @return OperationOutcome - requestObject in result field 
     */
    public static function fromGlobals(): OperationOutcome
    {
        try {
            $psr17Factory = new Psr17Factory();
            $creator = new ServerRequestCreator(
                $psr17Factory,
                $psr17Factory,
                $psr17Factory,
                $psr17Factory
            );
            
            $psrRequest = $creator->fromGlobals();
            return Operation::success($psrRequest);
        } catch (\Throwable $e) {
            return Operation::error(
                Operation::HTTP_INTERNAL_SERVER_ERROR, 
                'Failed to create request', 
                ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
        }
    }
}