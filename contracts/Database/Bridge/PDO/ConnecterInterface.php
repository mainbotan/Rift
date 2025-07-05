<?php

namespace Rift\Contracts\Database\Bridge\PDO;

use Rift\Core\Databus\OperationOutcome;

interface ConnecterInterface {
    public static function getPdoForSchema(string $schema): OperationOutcome;
}