<?php

namespace Rift\Core\Cache\Redis;

use Predis\ClientInterface;
use Rift\Contracts\Cache\CacheInterface;
use Rift\Core\Databus\Operation;
use Rift\Core\Databus\OperationOutcome;

class RedisCacheService implements CacheInterface
{
    public function __construct(
        private ClientInterface $redis
    ) {}

    public function get(string $key): OperationOutcome
    {
        try {
            $value = $this->redis->get($key);
            return Operation::success($value);
        } catch (\Exception $e) {
            return $this->wrapException($e, 'GET operation failed');
        }
    }

    public function set(string $key, mixed $value, ?int $ttl = null): OperationOutcome
    {
        try {
            if ($ttl !== null) {
                $result = $this->redis->setex($key, $ttl, $value);
            } else {
                $result = $this->redis->set($key, $value);
            }
            return Operation::success((bool)$result);
        } catch (\Exception $e) {
            return $this->wrapException($e, 'SET operation failed');
        }
    }

    public function delete(string|array $keys): OperationOutcome
    {
        try {
            $count = $this->redis->del((array)$keys);
            return Operation::success($count);
        } catch (\Exception $e) {
            return $this->wrapException($e, 'DEL operation failed');
        }
    }

    public function has(string $key): OperationOutcome
    {
        try {
            return Operation::success((bool)$this->redis->exists($key));
        } catch (\Exception $e) {
            return $this->wrapException($e, 'EXISTS operation failed');
        }
    }

    public function expire(string $key, int $ttl): OperationOutcome
    {
        try {
            return Operation::success((bool)$this->redis->expire($key, $ttl));
        } catch (\Exception $e) {
            return $this->wrapException($e, 'EXPIRE operation failed');
        }
    }

    public function increment(string $key, int $by = 1): OperationOutcome
    {
        try {
            return Operation::success($this->redis->incrby($key, $by));
        } catch (\Exception $e) {
            return $this->wrapException($e, 'INCR operation failed');
        }
    }

    public function ttl(string $key): OperationOutcome
    {
        try {
            return Operation::success($this->redis->ttl($key));
        } catch (\Exception $e) {
            return $this->wrapException($e, 'TTL operation failed');
        }
    }

    public function hSet(string $hash, string $field, mixed $value): OperationOutcome
    {
        try {
            return Operation::success((bool)$this->redis->hset($hash, $field, $value));
        } catch (\Exception $e) {
            return $this->wrapException($e, 'HSET operation failed');
        }
    }

    public function hGet(string $hash, string $field): OperationOutcome
    {
        try {
            return Operation::success($this->redis->hget($hash, $field));
        } catch (\Exception $e) {
            return $this->wrapException($e, 'HGET operation failed');
        }
    }

    public function pipeline(callable $callback): OperationOutcome
    {
        try {
            $pipeline = $this->redis->pipeline();
            $callback($pipeline);
            return Operation::success($pipeline->execute());
        } catch (\Exception $e) {
            return $this->wrapException($e, 'Pipeline execution failed');
        }
    }

    public function getClient(): ClientInterface
    {
        return $this->redis;
    }

    private function wrapException(\Exception $e, string $context): OperationOutcome
    {
        return Operation::error(
            Operation::HTTP_INTERNAL_SERVER_ERROR,
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