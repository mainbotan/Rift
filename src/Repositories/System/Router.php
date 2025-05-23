<?php

namespace Rift\Repositories\System;

use PDO;
use Rift\Core\Contracts\ResponseDTO;
use Rift\Core\Database\Connect;
use Rift\Core\Repositories\AbstractRouter;

class Router extends AbstractRouter {
    protected string $schema = 'system';

    /**
     * Конфигурация репозиториев
     */
    protected array $repositories = [
        'tenants.repo' => [
            'class' => \Rift\Repositories\System\TenantsRepository::class,
            'model' => \Rift\Models\System\Tenants::class
        ]
    ];
}