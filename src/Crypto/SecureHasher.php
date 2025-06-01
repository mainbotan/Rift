<?php

namespace Rift\Core\Crypto;

use Rift\Core\Contracts\Response;
use Rift\Core\Contracts\ResponseDTO;

class SecureHasher extends Response
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

    public function hash(string $password): ResponseDTO
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