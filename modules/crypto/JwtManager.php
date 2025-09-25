<?php

namespace Rift\Crypto;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Rift\Core\Databus\Result;
use Rift\Core\Databus\ResultType;

class JwtManager
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
            return Result::Failure(
                Result::HTTP_INTERNAL_SERVER_ERROR,
                'Unsupported JWT algorithm'
            );
        }
        $this->secretKey = $secretKey;
        $this->defaultTtl = $defaultTtl;
        $this->algorithm = $algorithm;
    }

    public function encode(array $payload, ?int $ttl = null): ResultType
    {
        try {
            $payload = array_merge($payload, [
                'iat' => time(),
                'exp' => time() + ($ttl ?? $this->defaultTtl),
                'jti' => bin2hex(random_bytes(16))
            ]);

            return Result::Success(JWT::encode($payload, $this->secretKey, $this->algorithm));
        } catch (\Exception $e) {
            return Result::Failure(Result::HTTP_BAD_REQUEST, $e->getMessage());
        }
    }

    public function decode(string $token): ResultType
    {
        try {
            return Result::Success(
                (array) JWT::decode($token, new Key($this->secretKey, $this->algorithm))
            );
        } catch (\Exception $e) {
            return Result::Failure(Result::HTTP_BAD_REQUEST, $e->getMessage());
        }
    }

    public function validate(string $token): ResultType
    {
        try {
            $this->decode($token);
            return Result::Success(true);
        } catch (\Exception $e) {
            return Result::Failure(Result::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    public function checkExpiration(array $decodedToken): ResultType
    {
        try {
            if (!isset($decodedToken['exp'])) {
                return Result::Failure(
                    Result::HTTP_BAD_REQUEST,
                    'Token does not contain expiration time'
                );
            }

            $currentTime = time();
            $expirationTime = $decodedToken['exp'];

            if ($currentTime > $expirationTime) {
                return Result::Failure(
                    Result::HTTP_UNAUTHORIZED,
                    'Token has expired',
                    ['expired_at' => $expirationTime]
                );
            }

            $remainingTime = $expirationTime - $currentTime;

            return Result::Success([
                'is_valid' => true,
                'remaining_seconds' => $remainingTime,
                'expires_at' => $expirationTime
            ]);
        } catch (\Exception $e) {
            return Result::Failure(
                Result::HTTP_INTERNAL_SERVER_ERROR,
                'Failed to check token expiration: ' . $e->getMessage()
            );
        }
    }
}