<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Text emitter.
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Core\Http\ResponseEmitters;

use Rift\Core\Databus\ResultType;
use Rift\Core\Http\ResponseEmitters\Emitter;
use Psr\Http\Message\ServerRequestInterface;

class TextEmitter extends Emitter {
    public function emit(ResultType $outcome, ServerRequestInterface $request): void {
        $this->setHeaders('text/plain');
        
        $text = sprintf(
            "Status: %s\nCode: %d\nPayload: %s\nMeta: %s",
            $outcome->isSuccess() ? 'OK' : 'ERROR',
            $outcome->code,
            print_r($outcome->result ?? $outcome->error, true),
            print_r($outcome->meta, true)
        );
        
        echo $text;
    }

    public function supports(string $contentType): bool {
        return $contentType === 'text/plain';
    }
}