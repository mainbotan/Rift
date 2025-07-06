<?php

namespace Rift\Core\Http\ResponseEmitters;

use Rift\Contracts\Http\ResponseEmitter\EmitterInterface;

abstract class AbstractEmitter implements EmitterInterface {
    protected function setHeaders(string $contentType): void {
        header("Content-Type: $contentType");
        header("X-Response-Emitter: " . static::class);
    }
}