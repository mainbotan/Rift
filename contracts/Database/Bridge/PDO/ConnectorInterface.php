<?php

namespace Rift\Contracts\Database\Bridge\PDO;

use Rift\Core\Databus\OperationOutcome;

interface ConnectorInterface {
    
    public static function fromEnv(array $env): self;

    public function createAdminConnection(): OperationOutcome;

    public function createSchemaConnection(string $schema): OperationOutcome;
}