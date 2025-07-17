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
        $validatedData = []; // Сюда будем собирать валидные данные

        foreach ($schema as $field => $rules) {
            $value = $data[$field] ?? null;
            $hasDefault = array_key_exists('default', $rules);
            
            // Если поле не передано и есть default - подставляем его
            if ($value === null && $hasDefault) {
                $value = $rules['default'];
            }

            $isOptional = $rules['optional'] ?? false;

            // Отсутствие обязательного поля (без default)
            if ($value === null && !$isOptional && !$hasDefault) {
                return Operation::error(
                    Operation::HTTP_BAD_REQUEST, 
                    $rules['message'] ?? "Missing required field: $field"
                );
            }

            // Пропускаем необязательное поле, если оно null
            if ($value === null && ($isOptional || $hasDefault)) {
                $validatedData[$field] = $value; // Сохраняем null или default
                continue;
            }

            $type = $rules['type'] ?? 'string';

            switch ($type) {
                case 'string':
                    if (!is_scalar($value) && !method_exists($value, '__toString')) {
                        return Operation::error(
                            Operation::HTTP_BAD_REQUEST, 
                            $rules['message'] ?? "$field must be a string"
                        );
                    }
                    $value = (string)$value;
                    $lengthMin = $rules['min'] ?? 0;
                    $lengthMax = $rules['max'] ?? PHP_INT_MAX;
                    $result = StringUtils::checkLength($value, $lengthMin, $lengthMax, $field);
                    if ($result->code !== Operation::HTTP_OK) return $result;

                    if (isset($rules['enum']) && !in_array($value, $rules['enum'], true)) {
                        return Operation::error(
                            Operation::HTTP_BAD_REQUEST, 
                            $rules['message'] ?? "$field must be one of: " . implode(', ', $rules['enum'])
                        );
                    }
                    break;

                case 'int':
                    if (!is_numeric($value)) {
                        return Operation::error(
                            Operation::HTTP_BAD_REQUEST, 
                            $rules['message'] ?? "$field must be an integer"
                        );
                    }
                    $value = (int)$value;
                    $min = $rules['min'] ?? PHP_INT_MIN;
                    $max = $rules['max'] ?? PHP_INT_MAX;
                    $result = IntUtils::checkRange($value, $min, $max, $field);
                    if ($result->code !== Operation::HTTP_OK) return $result;

                    if (isset($rules['enum']) && !in_array($value, $rules['enum'], true)) {
                        return Operation::error(
                            Operation::HTTP_BAD_REQUEST, 
                            $rules['message'] ?? "$field must be one of: " . implode(', ', $rules['enum'])
                        );
                    }
                    break;

                case 'float':
                    if (!is_numeric($value)) {
                        return Operation::error(
                            Operation::HTTP_BAD_REQUEST, 
                            $rules['message'] ?? "$field must be a float"
                        );
                    }
                    $value = (float)$value;
                    break;

                case 'bool':
                    if (is_string($value)) {
                        $value = strtolower($value);
                        if ($value === 'true') $value = true;
                        elseif ($value === 'false') $value = false;
                    }
                    if (!is_bool($value) && !in_array($value, [0, 1, '0', '1'], true)) {
                        return Operation::error(
                            Operation::HTTP_BAD_REQUEST, 
                            $rules['message'] ?? "$field must be a boolean"
                        );
                    }
                    $value = (bool)$value;
                    break;

                case 'array':
                    if (!is_array($value)) {
                        return Operation::error(
                            Operation::HTTP_BAD_REQUEST, 
                            $rules['message'] ?? "$field must be an array"
                        );
                    }
                    
                    if (isset($rules['schema'])) {
                        $validatedItems = [];
                        foreach ($value as $item) {
                            if (!is_array($item)) {
                                return Operation::error(
                                    Operation::HTTP_BAD_REQUEST, 
                                    "Each item in $field must be an object"
                                );
                            }
                            $res = self::validate($rules['schema'], $item);
                            if ($res->code !== Operation::HTTP_OK) return $res;
                            $validatedItems[] = $res->result; // Сохраняем валидированные данные
                        }
                        $value = $validatedItems;
                    }
                    break;

                default:
                    return Operation::error(
                        Operation::HTTP_INTERNAL_SERVER_ERROR, 
                        "Unsupported type: {$type}"
                    );
            }

            // Кастомный валидатор
            if (isset($rules['validate']) && is_callable($rules['validate'])) {
                $customResult = $rules['validate']($value, $data);
                if ($customResult instanceof OperationOutcome && $customResult->code !== Operation::HTTP_OK) {
                    return $customResult;
                } elseif ($customResult === false) {
                    return Operation::error(
                        Operation::HTTP_BAD_REQUEST, 
                        $rules['message'] ?? "$field failed custom validation"
                    );
                }
            }

            $validatedData[$field] = $value; // Сохраняем валидированное значение
        }

        // Возвращаем только поля из схемы (фильтрация лишних данных)
        return Operation::success($validatedData);
    }
}