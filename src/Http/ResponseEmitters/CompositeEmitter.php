<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * PDO Connection Router Interface.
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Core\Http\ResponseEmitters;

use Rift\Core\Databus\OperationOutcome;
use Rift\Contracts\Http\ResponseEmitter\EmitterInterface;
use Psr\Http\Message\ServerRequestInterface;

class CompositeEmitter implements EmitterInterface {
    public function __construct(
        /** @param EmitterInterface[] $emitters */
        private array $emitters
    ) {}

    public function emit(OperationOutcome $outcome, ServerRequestInterface $request): void {
        $acceptHeader = $request->getHeaderLine('HTTP_ACCEPT') ?? 'application/json';
        
        foreach ($this->emitters as $emitter) {
            if ($emitter->supports($acceptHeader)) {
                $emitter->emit($outcome);
                return;
            }
        }
        
        // Fallback to JSON
        (new JsonEmitter())->emit($outcome, $request);
    }

    public function supports(string $contentType): bool {
        return true; // Всегда поддерживает, так как есть fallback
    }
}