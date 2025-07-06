<?php

namespace Rift\Contracts\Http\Request;

use Psr\Http\Message\ServerRequestInterface;
use Rift\Core\Databus\OperationOutcome;

interface RequestInterface {
    /**
     * Getting PSR-7 request object
     * @return OperationOutcome - requestObject in result field 
     */
    public static function fromGlobals(): OperationOutcome;
}