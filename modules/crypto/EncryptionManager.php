<?php
namespace Rift\Crypto;

use Rift\Core\Databus\Result;
use Rift\Core\Databus\ResultType;

class EncryptionManager
{
    public function __construct(
        private string $cipher = 'AES-256-CBC',
        private string $keyDerivation = 'sha256',
        private string $key
    ) {
        if (!in_array($this->cipher, openssl_get_cipher_methods())) {
            return Result::Failure(
                Result::HTTP_INTERNAL_SERVER_ERROR,
                'Unsupported cipher: ' . $this->cipher
            );
        }
    }

    public function encrypt(string $data): ResultType
    {
        $key = $this->key;
        if (strlen($key) < 32) {
            return Result::Failure(
                Result::HTTP_BAD_REQUEST,
                'Encryption key must be at least 32 characters'
            );
        }

        try {
            $iv = random_bytes(openssl_cipher_iv_length($this->cipher));
            $encrypted = openssl_encrypt(
                $data,
                $this->cipher,
                hash($this->keyDerivation, $key, true),
                OPENSSL_RAW_DATA,
                $iv
            );

            return Result::Success(base64_encode($iv . $encrypted));
        } catch (\Throwable $e) {
            return Result::Failure(
                Result::HTTP_INTERNAL_SERVER_ERROR,
                'Encryption failed',
                [
                    'debug' => $e->getMessage(),
                    'cipher' => $this->cipher,
                    'key_derivation' => $this->keyDerivation
                ]
            );
        }
    }

    public function decrypt(string $encrypted): ResultType
    {
        $key = $this->key;
        try {
            $data = base64_decode($encrypted);
            $ivLength = openssl_cipher_iv_length($this->cipher);
            $iv = substr($data, 0, $ivLength);
            $encryptedData = substr($data, $ivLength);

            $result = openssl_decrypt(
                $encryptedData,
                $this->cipher,
                hash($this->keyDerivation, $key, true),
                OPENSSL_RAW_DATA,
                $iv
            );

            return $result !== false
                ? Result::Success($result)
                : Result::Failure(
                    Result::HTTP_BAD_REQUEST,
                    'Decryption failed - invalid data or key',
                    ['openssl_error' => openssl_error_string()]
                );
        } catch (\Throwable $e) {
            return Result::Failure(
                Result::HTTP_INTERNAL_SERVER_ERROR,
                'Decryption failed',
                [
                    'debug' => $e->getMessage(),
                    'cipher' => $this->cipher
                ]
            );
        }
    }
}