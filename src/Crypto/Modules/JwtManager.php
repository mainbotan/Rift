<?php
namespace Rift\Core\Crypto\Modules;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Rift\Core\Contracts\Operation;
use Rift\Core\Contracts\OperationOutcome;

class JwtManager extends Operation
{
    private string $secretKey;
    private string $algorithm;
    private int $defaultTtl;

    public function __construct(
        string $secretKey,
        int $defaultTtl = 3600,
        string $algorithm = 'HS256'
    ) {
        if (!in_array($algorithm, ['HS256', 'HS384', 'HS512'])) {
            return self::error(
                self::HTTP_INTERNAL_SERVER_ERROR,
                'Unsupported JWT algorithm'
            );
        }
        $this->secretKey = $secretKey;
        $this->defaultTtl = $defaultTtl;
        $this->algorithm = $algorithm;
    }

    public function encode(array $payload, ?int $ttl = null): OperationOutcome
    {
        try {
            $payload = array_merge($payload, [
                'iat' => time(),
                'exp' => time() + ($ttl ?? $this->defaultTtl),
                'jti' => bin2hex(random_bytes(16))
            ]);

            return self::success(JWT::encode($payload, $this->secretKey, $this->algorithm));
        } catch (\Exception $e) {
            return self::error(self::HTTP_BAD_REQUEST, $e->getMessage());
        }
    }

    public function decode(string $token): OperationOutcome
    {
        try {
            return self::success(
                (array) JWT::decode($token, new Key($this->secretKey, $this->algorithm))
            );
        } catch (\Exception $e) {
            return self::error(self::HTTP_BAD_REQUEST, $e->getMessage());
        }
    }

    public function validate(string $token): OperationOutcome
    {
        try {
            $this->decode($token);
            return self::success(true);
        } catch (\Exception $e) {
            return self::error(self::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }
}