<?php

namespace Rift\Core\Contracts;

class Operation
{
    use OperationOutcomeTrait;

    /**
     * Success response contract
     * @param mixed $result - script result
     * @param int $code - script code
     * @param ?string $error - error message
     * @param ?array $internal - internal metrics
     */
    public static function response(
        mixed $result,
        int $code = self::HTTP_OK,
        ?string $error = null,
        ?array $meta = null
    ): OperationOutcome {
        return new OperationOutcome(
            code: $code,
            result: $result,
            error: $error,
            meta: $meta ?? [
                'metrics' => [],
                'debug' => []
            ]
        );
    }

    public static function success(
        mixed $result,
        ?array $metrics = null,
        ?array $debug = null
    ): OperationOutcome {
        return self::response(
            result: $result,
            meta: [
                'metrics' => $metrics ?? [],
                'debug' => $debug ?? []
            ]
        );
    }

    public static function error(
        int $code = self::HTTP_INTERNAL_SERVER_ERROR,
        ?string $message = 'unknown error',
        ?array $debug = null
    ): OperationOutcome {
        return self::response(
            result: null,
            code: $code,
            error: $message,
            meta: [
                'debug' => $debug ?? []
            ]
        );
    }
}