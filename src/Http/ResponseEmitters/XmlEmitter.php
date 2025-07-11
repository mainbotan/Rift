<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * XML Emitter.
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Core\Http\ResponseEmitters;

use SimpleXMLElement;
use Rift\Core\Databus\OperationOutcome;
use Rift\Core\Http\ResponseEmitters\Emitter;
use Psr\Http\Message\ServerRequestInterface;

class XmlEmitter extends Emitter {
    public function emit(OperationOutcome $outcome, ServerRequestInterface $request): void {
        $this->setHeaders('application/xml');
        
        $xml = new SimpleXMLElement('<response/>');
        $xml->addChild('ok', $outcome->isSuccess() ? 'true' : 'false');
        $xml->addChild('code', (string)$outcome->code);
        
        $payload = $xml->addChild('payload');
        $this->arrayToXml((array)($outcome->result ?? $outcome->error), $payload);
        
        echo $xml->asXML();
    }

    private function arrayToXml(array $data, SimpleXMLElement $xml): void {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars((string)$value));
            }
        }
    }

    public function supports(string $contentType): bool {
        return $contentType === 'application/xml';
    }
}