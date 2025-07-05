<?php

/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Dependency containerization.
 * |
 * |--------------------------------------------------------------------------
 */

namespace Rift\Core\Containers;

use Rift\Core\DataBus\Operation;
use Rift\Core\DataBus\OperationOutcome;

class DI extends Operation {
    private array $relations = [];

    public function reg(string $dependencyKey, string $dependencyClass): void {
        if (!isset($this->relations[$dependencyKey])) {
            $this->relations[$dependencyKey] = $dependencyClass;
        }
    }
    public function get(string $dependencyKey): OperationOutcome {
        if (isset($this->relations[$dependencyKey])) {
            return self::success($this->relations[$dependencyKey]);
        }
        return self::error(self::HTTP_INTERNAL_SERVER_ERROR, "Dependency by key {$dependencyKey} not found");
    }
}