<?php

namespace Rift\Containers;

use Rift\Core\Contracts\Response;
use Rift\Core\Contracts\ResponseDTO;
use Rift\Core\Containers\ContainerInterface;

class RepositoriesContainer extends Response implements ContainerInterface {

    /**
     * Входная точка доступа
     * @param string $key - ключ зависимости
     */
    public function get(string $key): ResponseDTO {
        return match ($key) {
            'users.repo' => $this->getUsersRepo()
        };
    }
    
    // Объявление зависимостей
    public function getUsersRepo() {
        return self::response('', self::HTTP_OK);
    }
}