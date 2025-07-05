<?php

namespace Rift\Core\Crypto\Modules;

use Rift\Core\DataBus\Operation;
use Rift\Core\DataBus\OperationOutcome;

class UUID extends Operation {

    public static function generate() {
        $uuid = bin2hex(random_bytes(8));
        return substr($uuid, 0, 4) . '-' . substr($uuid, 4, 4) . '-' . substr($uuid, 8, 4);
    }
}