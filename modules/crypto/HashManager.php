<?php

namespace Rift\Crypto;

use Rift\Core\Databus\Operation;
use Rift\Core\Databus\OperationOutcome;

class HashManager
{
    public function __construct(
        private string|int $algorithm = PASSWORD_ARGON2I,
        private array $options = []
    ) { }

    public function passwordHash(string $password): string
    {
        return password_hash($password, $this->algorithm, $this->options);
    }
}