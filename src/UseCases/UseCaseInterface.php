<?php

namespace Rift\Core\UseCases;

use Rift\Core\Contracts\OperationOutcome;

// Гарантирует контракт у всех юз кейсов

interface UseCaseInterface {
    public function execute(array $data): OperationOutcome;
}