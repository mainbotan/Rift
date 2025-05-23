<?php

namespace Rift\Configurators;

use Rift\Core\Database\Configurators\TenantConfigurator;

class AppTenantConfigurator extends TenantConfigurator {
    protected static array $models = [
        \Rift\Models\Tenant\Users::class
    ];
}