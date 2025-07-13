<?php
namespace Rift\Crypto;

use Rift\Core\Databus\Operation;
use Rift\Core\Databus\OperationOutcome;

class DataEncryptor
{
    public function __construct(
        private string $cipher = 'AES-256-CBC',
        private string $keyDerivation = 'sha256'
    ) {
        if (!in_array($this->cipher, openssl_get_cipher_methods())) {
            return Operation::error(
                Operation::HTTP_INTERNAL_SERVER_ERROR,
                'Unsupported cipher: ' . $this->cipher
            );
        }
    }

    public function encrypt(string $data, string $key): OperationOutcome
    {
        if (strlen($key) < 32) {
            return Operation::error(
                Operation::HTTP_BAD_REQUEST,
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

            return Operation::success(base64_encode($iv . $encrypted));
        } catch (\Throwable $e) {
            return Operation::error(
                Operation::HTTP_INTERNAL_SERVER_ERROR,
                'Encryption failed',
                [
                    'debug' => $e->getMessage(),
                    'cipher' => $this->cipher,
                    'key_derivation' => $this->keyDerivation
                ]
            );
        }
    }

    public function decrypt(string $encrypted, string $key): OperationOutcome
    {
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
                ? Operation::success($result)
                : Operation::error(
                    Operation::HTTP_BAD_REQUEST,
                    'Decryption failed - invalid data or key',
                    ['openssl_error' => openssl_error_string()]
                );
        } catch (\Throwable $e) {
            return Operation::error(
                Operation::HTTP_INTERNAL_SERVER_ERROR,
                'Decryption failed',
                [
                    'debug' => $e->getMessage(),
                    'cipher' => $this->cipher
                ]
            );
        }
    }
}