<?php

namespace Rift\Repositories\Tenant;

use PDO;
use Rift\Core\Contracts\ResponseDTO;
use Rift\Core\Database\Connect;
use Rift\Core\Repositories\AbstractRouter;

class Router extends AbstractRouter {
    public function __construct($tenantId)
    {
        $this->schema = "tenant_{$tenantId}";
    }
    public static function forTenant($tenantId): static {
        return new static($tenantId);
    }

    /**
     * Конфигурация репозиториев
     */
    protected array $repositories = [
        'users.repo' => [
            'class' => \Rift\Repositories\Tenant\UsersRepository::class,
            'model' => \Rift\Models\Tenant\Users::class
        ]
    ];
}