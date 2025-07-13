<?php

namespace Rift\Crypto;

use Rift\Core\Databus\Operation;
use Rift\Core\Databus\OperationOutcome;

class HashManager
{
    public function __construct(
        private string|int $key,
        private array $options = []
    ) { }

    public function passwordHash(string $password): string
    {
        return password_hash($password, $this->key, $this->options);
    }
}