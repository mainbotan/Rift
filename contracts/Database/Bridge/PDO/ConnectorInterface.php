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
namespace Rift\Contracts\Database\Bridge\PDO;

use Rift\Core\Databus\OperationOutcome;

interface ConnectorInterface {
    
    public static function fromEnv(array $env): self;

    public function createAdminConnection(): OperationOutcome;

    public function createSchemaConnection(string $schema): OperationOutcome;
}