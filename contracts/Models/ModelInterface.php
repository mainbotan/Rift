<?php

namespace Rift\Contracts\Models;

use Rift\Core\Databus\OperationOutcome;

interface ModelInterface {
    /**
     * getSchema public method
     */
    public static function getSchema(): array;
    
    /**
     * validate public method
     * @return OperationOutcome
     */
    public static function validate(array $data): OperationOutcome;

    /**
     * validateField public method
     * @return OperationOutcome
     */
    public static function validateField(string $field, $value): OperationOutcome;

    /**
     * getMigrationSQL public method 
     * @return string
     */
    public static function getMigrationSQL(): string;

    /**
     * validateModel public method 
     * @return OperationOutcome
     */
    public static function validateModel(): OperationOutcome;
}