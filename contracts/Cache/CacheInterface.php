<?php

namespace Rift\Contracts\Cache;

use Rift\Core\Databus\ResultType;

interface CacheInterface {
    public function get(string $key): ResultType;
    public function set(string $key, mixed $value, ?int $ttl = null): ResultType;
    public function delete(string|array $keys): ResultType;
    public function has(string $key): ResultType;
    public function expire(string $key, int $ttl): ResultType;
    public function increment(string $key, int $by = 1): ResultType;
    public function ttl(string $key): ResultType;
    public function hSet(string $hash, string $field, mixed $value): ResultType;
    public function hGet(string $hash, string $field): ResultType;
    public function pipeline(callable $callback): ResultType;
}