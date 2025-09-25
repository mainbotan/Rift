<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Output emitter interface.
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Contracts\Http\ResponseEmitter;

use Psr\Http\Message\ServerRequestInterface;
use Rift\Core\Databus\ResultType;

interface EmitterInterface {
    /**
     * emit public method
     * data output
     * @return void
     */
    public function emit(ResultType $outcome, ServerRequestInterface $request): void;

    /**
     * supports public method
     * checking content type field
     * @return bool
     */
    public function supports(string $contentType): bool;
}