<?php

namespace Rift\Repositories\Tenant;

use PDO;
use PDOStatement;
use Rift\Core\Contracts\ResponseDTO;
use Rift\Core\Repositories\AbstractRepository;

class UsersRepository extends AbstractRepository
{
    public function selectById(int $tenantId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM `tenants` 
            WHERE `id` = :id
        ");

        $stmt->bindValue(':id', $tenantId, PDO::PARAM_INT);
        return $this->executeQuery($stmt);
    }

    public function selectAll(int $limit = 10, int $offset = 0): ResponseDTO
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM `tenants` 
            LIMIT :limit 
            OFFSET :offset
        ");

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        return $this->executeQuery($stmt);
    }

    public function createUser(array $data): ResponseDTO
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (name, password, role) 
            VALUES (:name, :password, :role)
        ");
        
        $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':password', $data['password'], PDO::PARAM_STR);
        $stmt->bindValue(':role', $data['role'], PDO::PARAM_STR);
        
        return $this->executeQuery($stmt);
    }
}