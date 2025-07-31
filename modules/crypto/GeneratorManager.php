<?php
namespace Rift\Crypto;

use Rift\Core\Databus\Result;
use Rift\Core\Databus\ResultType;

class GeneratorManager
{
    public function __construct(
        private int $csrfLength = 32,
        private int $apiKeyLength = 64
    ) {
        if ($csrfLength < 16 || $csrfLength > 128) {
            return Result::Failure(
                Result::HTTP_INTERNAL_SERVER_ERROR,
                'CSRF token length must be between 16 and 128 bytes'
            );
        }

        if ($apiKeyLength < 32 || $apiKeyLength > 256) {
            return Result::Failure(
                Result::HTTP_INTERNAL_SERVER_ERROR,
                'API key length must be between 32 and 256 bytes'
            );
        }
    }

    public function createCsrfToken(): ResultType
    {
        try {
            return Result::Success(bin2hex(random_bytes($this->csrfLength)));
        } catch (\Throwable $e) {
            return Result::Failure(
                Result::HTTP_INTERNAL_SERVER_ERROR,
                'CSRF token generation failed',
                ['debug' => $e->getMessage()]
            );
        }
    }

    public function generateApiKey(): ResultType
    {
        try {
            return Result::Success(bin2hex(random_bytes($this->apiKeyLength / 2)));
        } catch (\Throwable $e) {
            return Result::Failure(
                Result::HTTP_INTERNAL_SERVER_ERROR,
                'API key generation failed',
                ['debug' => $e->getMessage()]
            );
        }
    }
}