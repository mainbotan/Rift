<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Output abstract emitter.
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Core\Http\ResponseEmitters;

use Rift\Contracts\Http\ResponseEmitter\EmitterInterface;

abstract class Emitter implements EmitterInterface {
    protected function setHeaders(string $contentType): void {
        header("Content-Type: $contentType");
        header("X-Response-Emitter: " . static::class);
    }
}