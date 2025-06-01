<?php

namespace Rift\Configurators;

use Rift\Core\Database\Configurators\SystemConfigurator;

class AppSystemConfigurator extends SystemConfigurator
{
    protected static array $models = [
        \Rift\Models\System\Plans::class,
        \Rift\Models\System\Tenants::class
    ];
} 