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
use Rift\Core\Databus\Result;
use Rift\Core\Databus\ResultType;
use Psr\Http\Message\ServerRequestInterface;
use Rift\Contracts\Http\Request\RequestInterface;

class Request implements RequestInterface
{
    /**
     * Getting PSR-7 request object
     * @return ResultType - requestObject in result field 
     */
    public static function fromGlobals(): ResultType
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
            return Result::Success($psrRequest);
        } catch (\Throwable $e) {
            return Result::Failure(
                Result::HTTP_INTERNAL_SERVER_ERROR, 
                'Failed to create request', 
                ['exception' => $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );
        }
    }
}