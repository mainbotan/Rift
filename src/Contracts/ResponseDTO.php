<?php

namespace Rift\Core\Contracts;

class ResponseDTO
{
    public function __construct(
        public int $code,
        public mixed $result,
        public ?string $error = null,
        public ?array $meta = null
    ) {}

    public function withMetric(string $key, mixed $value): self
    {
        $this->meta['metrics'][$key] = $value;
        return $this;
    }

    public function getMetric(string $key): mixed
    {
        return $this->meta['metrics'][$key] ?? null;
    }

    public function addDebugData(string $key, mixed $value): self
    {
        $this->meta['debug'][$key] = $value;
        return $this;
    }

    public function isSuccess() {
        if ($this->code === 200) {
            return true;
        }
        return false;
    }
}