<?php

namespace Rift\Core\Serializer;

use Rift\Core\Contracts\OperationOutcome;

interface ResponseSerializerInterface {
    public function serialize(OperationOutcome $o): string;
}