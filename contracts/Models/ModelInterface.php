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

interface ModelInterface {

    public function migrate(): string;
}