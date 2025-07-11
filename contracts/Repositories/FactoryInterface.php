<?php

/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Repositories factory interface.
 * |
 * |--------------------------------------------------------------------------
 */

namespace Rift\Contracts\Repositories;

use PDO;

interface FactoryInterface {
    public function __construct(PDO $pdo);
}