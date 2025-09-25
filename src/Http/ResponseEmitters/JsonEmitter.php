<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * JSON Emitter.
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Core\Http\ResponseEmitters;

use Psr\Http\Message\ServerRequestInterface;
use Rift\Core\Databus\ResultType;
use Rift\Core\Http\ResponseEmitters\Emitter;

class JsonEmitter extends Emitter {
    public function emit(ResultType $outcome, ServerRequestInterface $request): void {
        $this->setHeaders('application/json');
        
        echo json_encode([
            'ok' => $outcome->isSuccess(),
            'code' => $outcome->code,
            'payload' => $outcome->result ?? $outcome->error,
            '_meta' => $outcome->meta
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function supports(string $contentType): bool {
        return $contentType === 'application/json';
    }
}