<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * An auxiliary Operation class that can be used to simply create an ResultType object.
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Core\Databus;

final class Result
{
    use ResultTypeTrait;

    /**
     * Success response contract
     * @param bool $status
     * @param mixed $result script result
     * @param int $code script code
     * @param ?string $error error message
     * @param ?array $internal internal metrics
     */
    private static function response(
        bool $status,
        mixed $result,
        int $code = self::HTTP_OK,
        ?string $error = null,
        ?array $meta = null
    ): ResultType {
        return new ResultType(
            status: $status,
            code: $code,
            result: $result,
            error: $error,
            meta: $meta ?? [
                'metrics' => [],
                'debug' => []
            ]
        );
    }

    public static function Success(
        mixed $result = null,
        int $code = self::HTTP_OK,
        ?array $metrics = null,
        ?array $debug = null
    ): ResultType {
        return self::response(
            status: true,
            code: $code,
            result: $result,
            meta: [
                'metrics' => $metrics ?? [],
                'debug' => $debug ?? []
            ]
        );
    }

    public static function Failure(
        int $code = self::HTTP_INTERNAL_SERVER_ERROR,
        ?string $message = 'unknown error',
        ?array $debug = null
    ): ResultType {
        return self::response(
            status: false,
            result: null,
            code: $code,
            error: $message,
            meta: [
                'debug' => $debug ?? []
            ]
        );
    }
}