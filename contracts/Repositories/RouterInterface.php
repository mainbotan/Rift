<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Repositories router interface.
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Contracts\Repositories;

use Rift\Contracts\Database\Bridge\PDO\ConnectorInterface;
use Rift\Core\Databus\OperationOutcome;

interface RouterInterface {
    public function __construct(ConnectorInterface $connector);
    public function factory(): OperationOutcome;
}