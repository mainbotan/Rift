<?php

namespace Rift\Core\Containers;

use Rift\Core\Contracts\OperationOutcome;

// Гарантирует единую точку запроса у контейнеров

interface ContainerInterface {
    public function get(string $key): OperationOutcome;
}