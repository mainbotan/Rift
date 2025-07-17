<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Model interface.
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Contracts\Models;

use Rift\Core\Databus\OperationOutcome;

interface ModelInterface {

    public static function getTableName(): string;

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