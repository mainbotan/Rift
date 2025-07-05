<?php

namespace Rift\Contracts\Database\Configurators;

use Rift\Core\DataBus\OperationOutcome;

interface ConfiguratorInterface {
    /**
     * configure public method
     * configure database schema
     * @return OperationOutcome
     */
    public static function configure(): OperationOutcome;
}