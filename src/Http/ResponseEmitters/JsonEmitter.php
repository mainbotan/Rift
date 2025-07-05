<?php

namespace Rift\Core\Http\ResponseEmitters;

use Rift\Core\Databus\OperationOutcome;
use Rift\Core\Http\ResponseEmitters\AbstractEmitter;

class JsonEmitter extends AbstractEmitter {
    public function emit(OperationOutcome $outcome): void {
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