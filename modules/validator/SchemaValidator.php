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

namespace Rift\Validator;

use Rift\Core\Databus\Operation;
use Rift\Core\Databus\OperationOutcome;
use Rift\Validator\Types\IntUtils;
use Rift\Validator\Types\StringUtils;

class SchemaValidator
{
    public static function validate(array $schema, array $data): OperationOutcome
    {
        foreach ($schema as $field => $rules) {
            $value = $data[$field] ?? ($rules['default'] ?? null);
            $isOptional = $rules['optional'] ?? false;

            // Отсутствие обязательного
            if ($value === null && !$isOptional) {
                return Operation::error(Operation::HTTP_BAD_REQUEST, $rules['message'] ?? "Missing required field: $field");
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
                    if ($result->code !== Operation::HTTP_OK) return $result;

                    if (isset($rules['enum']) && !in_array($value, $rules['enum'], true)) {
                        return Operation::error(Operation::HTTP_BAD_REQUEST, $rules['message'] ?? "$field must be one of: " . implode(', ', $rules['enum']));
                    }
                    break;

                case 'int':
                    if (!is_numeric($value)) {
                        return Operation::error(Operation::HTTP_BAD_REQUEST, $rules['message'] ?? "$field must be an integer");
                    }
                    $value = (int)$value;
                    $min = $rules['min'] ?? PHP_INT_MIN;
                    $max = $rules['max'] ?? PHP_INT_MAX;
                    $result = IntUtils::checkRange($value, $min, $max, $field);
                    if ($result->code !== Operation::HTTP_OK) return $result;

                    if (isset($rules['enum']) && !in_array($value, $rules['enum'], true)) {
                        return Operation::error(Operation::HTTP_BAD_REQUEST, $rules['message'] ?? "$field must be one of: " . implode(', ', $rules['enum']));
                    }
                    break;

                case 'float':
                    if (!is_numeric($value)) {
                        return Operation::error(Operation::HTTP_BAD_REQUEST, $rules['message'] ?? "$field must be a float");
                    }
                    $value = (float)$value;
                    break;

                case 'bool':
                    if (!is_bool($value) && !in_array($value, ['true', 'false', 0, 1, '0', '1'], true)) {
                        return Operation::error(Operation::HTTP_BAD_REQUEST, $rules['message'] ?? "$field must be a boolean");
                    }
                    break;

                case 'array':
                    if (!is_array($value)) {
                        return Operation::error(Operation::HTTP_BAD_REQUEST, $rules['message'] ?? "$field must be an array");
                    }
                    
                    if (isset($rules['schema'])) {
                        foreach ($value as $item) {
                            if (!is_array($item)) {
                                return Operation::error(Operation::HTTP_BAD_REQUEST, "Each item in $field must be an object");
                            }
                            $res = self::validate($rules['schema'], $item);
                            if ($res->code !== Operation::HTTP_OK) return $res;
                        }
                    }
                    break;

                default:
                    return Operation::error(Operation::HTTP_INTERNAL_SERVER_ERROR, "Unsupported type: {$type}");
            }

            // Кастомный валидатор
            if (isset($rules['validate']) && is_callable($rules['validate'])) {
                $customResult = $rules['validate']($value, $data);
                if ($customResult instanceof OperationOutcome && $customResult->code !== Operation::HTTP_OK) {
                    return $customResult;
                } elseif ($customResult === false) {
                    return Operation::error(Operation::HTTP_BAD_REQUEST, $rules['message'] ?? "$field failed custom validation");
                }
            }
        }

        return Operation::success(null);
    }
}