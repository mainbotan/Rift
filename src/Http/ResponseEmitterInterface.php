<?php

namespace Rift\Core\Http;

use Rift\Core\Contracts\OperationOutcome;

interface ResponseEmitterInterface {
    public function emit(OperationOutcome $outcome): void;
}
