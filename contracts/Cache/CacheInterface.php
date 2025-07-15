<?php

namespace Rift\Contracts\Cache;

use Rift\Core\Databus\OperationOutcome;

interface CacheInterface {
    public function get(string $key): OperationOutcome;
    public function set(string $key, mixed $value, ?int $ttl = null): OperationOutcome;
    public function delete(string|array $keys): OperationOutcome;
    public function has(string $key): OperationOutcome;
    public function expire(string $key, int $ttl): OperationOutcome;
    public function increment(string $key, int $by = 1): OperationOutcome;
    public function ttl(string $key): OperationOutcome;
    public function hSet(string $hash, string $field, mixed $value): OperationOutcome;
    public function hGet(string $hash, string $field): OperationOutcome;
    public function pipeline(callable $callback): OperationOutcome;
}