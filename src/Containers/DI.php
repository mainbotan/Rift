<?php

namespace Rift\Core\Containers;

use Rift\Core\Contracts\Operation;
use Rift\Core\Contracts\OperationOutcome;

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