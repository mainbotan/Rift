<?php

namespace Rift\Core\Http\ResponseEmitters;

use Rift\Core\DataBus\OperationOutcome;

interface EmitterInterface {
    public function emit(OperationOutcome $outcome): void;
    public function supports(string $contentType): bool;
}
