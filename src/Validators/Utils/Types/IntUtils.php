<?php

/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Utilities for validating integers.
 * |
 * |--------------------------------------------------------------------------
 */

namespace Rift\Core\Validators\Utils\Types;

use Rift\Core\Databus\Operation;

class IntUtils extends Operation
{
    public static function checkRange(
        int $value,
        int $min,
        int $max,
        string $fieldName = 'value'
    ) {
        if ($value < $min || $value > $max) {
            return self::error(self::HTTP_BAD_REQUEST, "$fieldName must be between $min and $max");
        }

        return self::success(null);
    }

    public static function isPositive(int $value, string $fieldName = 'value')
    {
        if ($value < 0) {
            return self::error(self::HTTP_BAD_REQUEST, "$fieldName must be positive");
        }

        return self::success(null);
    }
}
