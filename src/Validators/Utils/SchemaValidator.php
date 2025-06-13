<?php

/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Validation of complex schemes.
 * |
 * |--------------------------------------------------------------------------
 */

namespace Rift\Core\Validators\Utils;

use Rift\Core\Contracts\Operation;
use Rift\Core\Contracts\OperationOutcome;
use Rift\Core\Validators\Utils\Types\IntUtils;
use Rift\Core\Validators\Utils\Types\StringUtils;

class SchemaValidator extends Operation
{
    public static function validate(array $schema, array $data): OperationOutcome
    {
        foreach ($schema as $field => $rules) {
            $value = $data[$field] ?? ($rules['default'] ?? null);
            $isOptional = $rules['optional'] ?? false;

            // Отсутствие обязательного
            if ($value === null && !$isOptional) {
                return self::error(self::HTTP_BAD_REQUEST, $rules['message'] ?? "Missing required field: $field");
            }

            // Не проверяем необязательное отсутствующее
            if ($value === null && $isOptional) {
                continue;
            }

            $type = $rules['type'] ?? 'string';

            switch ($type) {
                case 'string':
                    $lengthMin = $rules['min'] ?? 0;
                    $lengthMax = $rules['max'] ?? PHP_INT_MAX;
                    $result = StringUtils::checkLength((string)$value, $lengthMin, $lengthMax, $field);
                    if ($result->code !== self::HTTP_OK) return $result;

                    if (isset($rules['enum']) && !in_array($value, $rules['enum'], true)) {
                        return self::error(self::HTTP_BAD_REQUEST, $rules['message'] ?? "$field must be one of: " . implode(', ', $rules['enum']));
                    }
                    break;

                case 'int':
                    if (!is_numeric($value)) {
                        return self::error(self::HTTP_BAD_REQUEST, $rules['message'] ?? "$field must be an integer");
                    }
                    $value = (int)$value;
                    $min = $rules['min'] ?? PHP_INT_MIN;
                    $max = $rules['max'] ?? PHP_INT_MAX;
                    $result = IntUtils::checkRange($value, $min, $max, $field);
                    if ($result->code !== self::HTTP_OK) return $result;

                    if (isset($rules['enum']) && !in_array($value, $rules['enum'], true)) {
                        return self::error(self::HTTP_BAD_REQUEST, $rules['message'] ?? "$field must be one of: " . implode(', ', $rules['enum']));
                    }
                    break;

                case 'float':
                    if (!is_numeric($value)) {
                        return self::error(self::HTTP_BAD_REQUEST, $rules['message'] ?? "$field must be a float");
                    }
                    $value = (float)$value;
                    break;

                case 'bool':
                    if (!is_bool($value) && !in_array($value, ['true', 'false', 0, 1, '0', '1'], true)) {
                        return self::error(self::HTTP_BAD_REQUEST, $rules['message'] ?? "$field must be a boolean");
                    }
                    break;

                case 'array':
                    if (!is_array($value)) {
                        return self::error(self::HTTP_BAD_REQUEST, $rules['message'] ?? "$field must be an array");
                    }
                    
                    if (isset($rules['schema'])) {
                        foreach ($value as $item) {
                            if (!is_array($item)) {
                                return self::error(self::HTTP_BAD_REQUEST, "Each item in $field must be an object");
                            }
                            $res = self::validate($rules['schema'], $item);
                            if ($res->code !== self::HTTP_OK) return $res;
                        }
                    }
                    break;

                default:
                    return self::error(500, "Unsupported type: {$type}");
            }

            // Кастомный валидатор
            if (isset($rules['validate']) && is_callable($rules['validate'])) {
                $customResult = $rules['validate']($value, $data);
                if ($customResult instanceof OperationOutcome && $customResult->code !== self::HTTP_OK) {
                    return $customResult;
                } elseif ($customResult === false) {
                    return self::error(self::HTTP_BAD_REQUEST, $rules['message'] ?? "$field failed custom validation");
                }
            }
        }

        return self::success(null);
    }
}
