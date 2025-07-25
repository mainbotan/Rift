<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Database schemas configurator Interface.
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Contracts\Database\Configurators;

use Rift\Core\Databus\OperationOutcome;

interface ConfiguratorInterface {
    /**
     * configure public method
     * configure database schema
     * @return OperationOutcome
     */
    public function configure(): OperationOutcome;

    public static function registerTenantModel(string $modelClass): void;

    public static function registerSystemModel(string $modelClass): void;

    public function forTenant(string $tenantId, string $prefix = 'tenant_'): self;

    public function forSystem(): self;

    public function skipSchemaCreation(): self;
}