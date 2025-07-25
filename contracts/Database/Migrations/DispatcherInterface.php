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
namespace Rift\Contracts\Database\Migrations;

use Rift\Core\Databus\OperationOutcome;

interface DispatcherInterface {

    public function configure(): OperationOutcome;

    public function model(string $modelLink): self;

    public function forTenant(string $tenantId, string $prefix = 'tenant_'): self;

    public function forSystem(): self;
}