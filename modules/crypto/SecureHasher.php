<?php

namespace Rift\Core\Crypto\Modules;

use Rift\Core\Databus\Operation;
use Rift\Core\Databus\OperationOutcome;

class SecureHasher
{
    public function __construct(
        private string|int $algorithm = PASSWORD_ARGON2ID,
        private array $options = []
    ) {
        if (!in_array($algorithm, [PASSWORD_BCRYPT, PASSWORD_ARGON2I, PASSWORD_ARGON2ID])) {
            return Operation::error(
                Operation::HTTP_INTERNAL_SERVER_ERROR,
                'Unsupported hashing algorithm'
            );
        }
    }

    public function hash(string $password): OperationOutcome
    {
        $hash = password_hash($password, $this->algorithm, $this->options);
        
        return $hash
            ? Operation::success($hash)
            : Operation::error(
                Operation::HTTP_INTERNAL_SERVER_ERROR,
                'Hashing failed'
            );
    }
}