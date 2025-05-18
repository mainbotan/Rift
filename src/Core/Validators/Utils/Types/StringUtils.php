<?php

namespace Rift\Core\Validators\Utils\Types;

use Rift\Core\Contracts\Response;

class StringUtils extends Response
{
    public static function checkLength(
        string $value,
        int $min,
        int $max,
        string $fieldName = 'string'
    ) {
        $length = mb_strlen($value);

        if ($length < $min) {
            return self::error(400, "$fieldName must be at least $min characters");
        }

        if ($length > $max) {
            return self::error(400, "$fieldName must be no more than $max characters");
        }

        return self::success(null);
    }

    public static function notEmpty(string $value, string $fieldName = 'string')
    {
        if (trim($value) === '') {
            return self::error(400, "$fieldName cannot be empty");
        }

        return self::success(null);
    }
}
