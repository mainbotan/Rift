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
namespace Rift\Contracts\Database\Models;

interface ModelInterface {
    const NAME = '';
    const VERSION = '1.0.0';
    
    public function migrate(): string;
}