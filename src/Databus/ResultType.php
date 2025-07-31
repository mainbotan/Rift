<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * The ResultType object + methods for processing it.
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Core\Databus;

final class ResultType
{
    use ResultTypeTrait;

    public function __construct(
        public bool $status,
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
    public function getDebug(string $key): mixed
    {
        return $this->meta['debug'][$key] ?? null;
    }

    public function isSuccess() {
        return $this->status;
    }

    /**
     * Выполняет коллбэк, если результат успешный (аналог then/flatMap)
     */
    public function then(callable $callback): self
    {
        if (!$this->isSuccess()) {
            return $this;
        }
        return $callback($this->result);
    }

    /**
     * Трансформирует результат, если успех (аналог map)
     */
    public function map(callable $callback): self
    {
        if (!$this->isSuccess()) {
            return $this;
        }
        return new self(
            $this->code,
            $callback($this->result),
            $this->error,
            $this->meta
        );
    }

    /**
     * Обрабатывает ошибку, если она есть (аналог catch)
     */
    public function catch(callable $errorHandler): self
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return $errorHandler($this->error, $this->code, $this->meta);
    }

    /**
     * Выполняет сайд-эффект без изменения результата (аналог tap)
     */
    public function tap(callable $callback): self
    {
        $callback($this->result, $this->error, $this->meta);
        return $this;
    }

    /**
     * Проверяет условие, иначе возвращает ошибку (аналог filter/assert)
     */
    public function ensure(callable $predicate, string $errorMessage, int $errorCode = 400): self
    {
        if (!$this->isSuccess()) {
            return $this;
        }
        if (!$predicate($this->result)) {
            return new self(false, $errorCode, null, $errorMessage, $this->meta);
        }
        return $this;
    }

    /**
     * Комбинирует два OperationOutcome (аналог zip)
     */
    public function merge(ResultType $other, callable $merger): self
    {
        if (!$this->isSuccess()) {
            return $this;
        }
        if (!$other->isSuccess()) {
            return $other;
        }
        return new self(
            $this->code,
            $merger($this->result, $other->result),
            null,
            array_merge_recursive($this->meta, $other->meta)
        );
    }

    public function toJson(
        ?callable $transformer = null,
        int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    ): string {
        $data = $transformer ? $transformer($this) : [
            'status' => $this->isSuccess() ? 'success' : 'error',
            'code' => $this->code,
            'result' => $this->result,
            'error' => $this->error,
            'meta' => $this->meta
        ];

        return json_encode($data, $flags);
    }
}