<?php

namespace Rift\Crypto;

class UidManager {

    public static function generate(): string {
        $uuid = bin2hex(random_bytes(8));
        return substr($uuid, 0, 4) . '-' . substr($uuid, 4, 4) . '-' . substr($uuid, 8, 4);
    }
    public static function toSchemaName(string $uid): string
    {
        return str_replace('-', '_', $uid);
    }
}