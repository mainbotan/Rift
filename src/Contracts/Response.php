<?php

namespace Rift\Core\Contracts;

class Response
{
    use ResponseTrait;

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
    ): ResponseDTO {
        return new ResponseDTO(
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
    ): ResponseDTO {
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
    ): ResponseDTO {
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