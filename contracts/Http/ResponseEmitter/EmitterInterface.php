<?php

namespace Rift\Contracts\Http\ResponseEmitter;

use Rift\Core\Databus\OperationOutcome;

interface EmitterInterface {
    /**
     * emit public method
     * data output
     * @return void
     */
    public function emit(OperationOutcome $outcome): void;

    /**
     * supports public method
     * checking content type field
     * @return bool
     */
    public function supports(string $contentType): bool;
}