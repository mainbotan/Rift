<?php

namespace Rift\Core\Crypto\Modules;

use Rift\Core\Contracts\Operation;
use Rift\Core\Contracts\OperationOutcome;

class UUID extends Operation {

    public static function generate() {
        $uuid = bin2hex(random_bytes(8));
        return substr($uuid, 0, 4) . '-' . substr($uuid, 4, 4) . '-' . substr($uuid, 8, 4);
    }
}