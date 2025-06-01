<?php

namespace Rift\Core\UseCases;

use Rift\Core\Contracts\ResponseDTO;

// Гарантирует контракт у всех юз кейсов

interface UseCaseInterface {
    public function execute(array $data): ResponseDTO;
}