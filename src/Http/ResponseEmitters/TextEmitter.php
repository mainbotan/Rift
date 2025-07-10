<?php

namespace Rift\Core\Http\ResponseEmitters;

use Rift\Core\Databus\OperationOutcome;
use Rift\Core\Http\ResponseEmitters\AbstractEmitter;
use Psr\Http\Message\ServerRequestInterface;

class TextEmitter extends AbstractEmitter {
    public function emit(OperationOutcome $outcome, ServerRequestInterface $request): void {
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