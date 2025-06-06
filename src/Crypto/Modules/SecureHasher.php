<?php

namespace Rift\Core\Crypto\Modules;

use Rift\Core\Contracts\Operation;
use Rift\Core\Contracts\OperationOutcome;

class SecureHasher extends Operation
{
    public function __construct(
        private string|int $algorithm = PASSWORD_ARGON2ID,
        private array $options = []
    ) {
        if (!in_array($algorithm, [PASSWORD_BCRYPT, PASSWORD_ARGON2I, PASSWORD_ARGON2ID])) {
            return self::error(
                self::HTTP_INTERNAL_SERVER_ERROR,
                'Unsupported hashing algorithm'
            );
        }
    }

    public function hash(string $password): OperationOutcome
    {
        $hash = password_hash($password, $this->algorithm, $this->options);
        
        return $hash
            ? self::success($hash)
            : self::error(
                self::HTTP_INTERNAL_SERVER_ERROR,
                'Hashing failed'
            );
    }
}