<?php

/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Utilities for validating strings.
 * |
 * |--------------------------------------------------------------------------
 */

namespace Rift\Core\Validators\Utils\Types;

use Rift\Core\Databus\Operation;

class StringUtils extends Operation
{
    public static function checkLength(
        string $value,
        int $min,
        int $max,
        string $fieldName = 'string'
    ) {
        $length = mb_strlen($value);

        if ($length < $min) {
            return self::error(self::HTTP_BAD_REQUEST, "$fieldName must be at least $min characters");
        }

        if ($length > $max) {
            return self::error(self::HTTP_BAD_REQUEST, "$fieldName must be no more than $max characters");
        }

        return self::success(null);
    }

    public static function notEmpty(string $value, string $fieldName = 'string')
    {
        if (trim($value) === '') {
            return self::error(self::HTTP_BAD_REQUEST, "$fieldName cannot be empty");
        }

        return self::success(null);
    }
}
