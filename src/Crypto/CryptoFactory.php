<?php
namespace Rift\Core\Crypto;

class CryptoFactory
{
    public function __construct(
        protected array $config
    ) {}

    public function getJwt(): JwtManager
    {
        $cfg = $this->config['JWT'] ?? [];
        return new JwtManager(
            $cfg['secretKey'] ?? throw new \InvalidArgumentException("JWT secretKey missing"),
            (int)($cfg['defaultTtl'] ?? 3600),
            $cfg['algorithm'] ?? 'HS256'
        );
    }

    public function getHasher(): SecureHasher
    {
        $cfg = $this->config['Hashing'] ?? [];
        return new SecureHasher(
            $cfg['algorithm'] ?? 'bcrypt',
            $cfg['options'] ?? []
        );
    }

    public function getEncryptor(): DataEncryptor
    {
        $cfg = $this->config['Encryption'] ?? [];
        return new DataEncryptor(
            $cfg['cipher'] ?? 'AES-256-CBC',
            $cfg['keyDerivation'] ?? 'default'
        );
    }

    public function getTokenGenerator(): TokenGenerator
    {
        $cfg = $this->config['Tokens'] ?? [];
        return new TokenGenerator(
            (int)($cfg['csrfLength'] ?? 32),
            (int)($cfg['apiKeyLength'] ?? 64)
        );
    }

    // Статические хелперы (опционально)
    public static function from(array $config): self
    {
        return new self($config);
    }
}
