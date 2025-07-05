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

namespace Rift\Validator\Types;

use Rift\Core\Databus\Operation;

class IntUtils
{
    public static function checkRange(
        int $value,
        int $min,
        int $max,
        string $fieldName = 'value'
    ) {
        if ($value < $min || $value > $max) {
            return Operation::error(Operation::HTTP_BAD_REQUEST, "$fieldName must be between $min and $max");
        }

        return Operation::success(null);
    }

    public static function isPositive(int $value, string $fieldName = 'value')
    {
        if ($value < 0) {
            return Operation::error(Operation::HTTP_BAD_REQUEST, "$fieldName must be positive");
        }

        return Operation::success(null);
    }
}
