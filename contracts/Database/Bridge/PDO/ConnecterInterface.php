<?php

namespace Rift\Contracts\Database\Bridge\PDO;

use Rift\Core\DataBus\OperationOutcome;

interface ConnecterInterface {
    public static function getPdoForSchema(string $schema): OperationOutcome;
}