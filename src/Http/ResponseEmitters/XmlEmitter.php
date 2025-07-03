<?php

namespace Rift\Core\Http\ResponseEmitters;

use SimpleXMLElement;
use Rift\Core\Contracts\OperationOutcome;
use Rift\Core\Http\ResponseEmitters\AbstractEmitter;

class XmlEmitter extends AbstractEmitter {
    public function emit(OperationOutcome $outcome): void {
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