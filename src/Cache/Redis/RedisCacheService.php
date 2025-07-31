<?php

namespace Rift\Core\Cache\Redis;

use Predis\ClientInterface;
use Rift\Contracts\Cache\CacheInterface;
use Rift\Core\Databus\Result;
use Rift\Core\Databus\ResultType;

class RedisCacheService implements CacheInterface
{
    public function __construct(
        private ClientInterface $redis
    ) {}

    public function get(string $key): ResultType
    {
        try {
            $value = $this->redis->get($key);
            return Result::Success($value);
        } catch (\Exception $e) {
            return $this->wrapException($e, 'GET operation failed');
        }
    }

    public function set(string $key, mixed $value, ?int $ttl = null): ResultType
    {
        try {
            if ($ttl !== null) {
                $result = $this->redis->setex($key, $ttl, $value);
            } else {
                $result = $this->redis->set($key, $value);
            }
            return Result::Success((bool)$result);
        } catch (\Exception $e) {
            return $this->wrapException($e, 'SET operation failed');
        }
    }

    public function delete(string|array $keys): ResultType
    {
        try {
            $count = $this->redis->del((array)$keys);
            return Result::Success($count);
        } catch (\Exception $e) {
            return $this->wrapException($e, 'DEL operation failed');
        }
    }

    public function has(string $key): ResultType
    {
        try {
            return Result::Success((bool)$this->redis->exists($key));
        } catch (\Exception $e) {
            return $this->wrapException($e, 'EXISTS operation failed');
        }
    }

    public function expire(string $key, int $ttl): ResultType
    {
        try {
            return Result::Success((bool)$this->redis->expire($key, $ttl));
        } catch (\Exception $e) {
            return $this->wrapException($e, 'EXPIRE operation failed');
        }
    }

    public function increment(string $key, int $by = 1): ResultType
    {
        try {
            return Result::Success($this->redis->incrby($key, $by));
        } catch (\Exception $e) {
            return $this->wrapException($e, 'INCR operation failed');
        }
    }

    public function ttl(string $key): ResultType
    {
        try {
            return Result::Success($this->redis->ttl($key));
        } catch (\Exception $e) {
            return $this->wrapException($e, 'TTL operation failed');
        }
    }

    public function hSet(string $hash, string $field, mixed $value): ResultType
    {
        try {
            return Result::Success((bool)$this->redis->hset($hash, $field, $value));
        } catch (\Exception $e) {
            return $this->wrapException($e, 'HSET operation failed');
        }
    }

    public function hGet(string $hash, string $field): ResultType
    {
        try {
            return Result::Success($this->redis->hget($hash, $field));
        } catch (\Exception $e) {
            return $this->wrapException($e, 'HGET operation failed');
        }
    }

    public function pipeline(callable $callback): ResultType
    {
        try {
            $pipeline = $this->redis->pipeline();
            $callback($pipeline);
            return Result::Success($pipeline->execute());
        } catch (\Exception $e) {
            return $this->wrapException($e, 'Pipeline execution failed');
        }
    }

    public function getClient(): ClientInterface
    {
        return $this->redis;
    }

    private function wrapException(\Exception $e, string $context): ResultType
    {
        return Result::Failure(
            Result::HTTP_INTERNAL_SERVER_ERROR,
            'Redis error: ' . $context,
            [
                'exception' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode()
                ]
            ]
        );
    }
}