<?php

namespace Rift\Core\Http\ResponseEmitters;

use Rift\Core\Databus\OperationOutcome;
use Rift\Core\Http\ResponseEmitters\EmitterInterface;

class CompositeEmitter implements EmitterInterface {
    public function __construct(
        /** @param EmitterInterface[] $emitters */
        private array $emitters
    ) {}

    public function emit(OperationOutcome $outcome): void {
        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? 'application/json';
        
        foreach ($this->emitters as $emitter) {
            if ($emitter->supports($acceptHeader)) {
                $emitter->emit($outcome);
                return;
            }
        }
        
        // Fallback to JSON
        (new JsonEmitter())->emit($outcome);
    }

    public function supports(string $contentType): bool {
        return true; // Всегда поддерживает, так как есть fallback
    }
}