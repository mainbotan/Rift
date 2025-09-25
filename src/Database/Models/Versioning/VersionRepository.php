<?php

namespace Rift\Core\Database\Models\Versioning;

use PDO;
use PDOStatement;
use Rift\Core\Databus\Result;
use Rift\Core\Databus\ResultType;
use Rift\Core\Repositories\Repository;

class VersionRepository extends Repository
{
    public function getTableVersion(string $name): ResultType 
    {
        $stmt = $this->pdo->prepare("SELECT version FROM versions WHERE table_name=:name");
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        return $this->executeQuery($stmt)
            ->then(function($result){
                if (!isset($result[0]['version'])) {
                    return Result::Success(null);
                }
                return Result::Success($result[0]['version']);
            });
    }
    public function updateTable(string $name, string $version): ResultType 
    {
        $stmt = $this->pdo->prepare("UPDATE versions SET version=:version WHERE table_name=:name");
        $this->bindParams($stmt, $name, $version);
        return $this->executeQuery($stmt);
    }
    public function createTable(string $name, string $version): ResultType
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO versions (table_name, version) 
            VALUES (:name, :version)
        ");
        $this->bindParams($stmt, $name, $version);
        return $this->executeQuery($stmt);
    }
    private function bindParams(PDOStatement $stmt, $name, $version) {
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':version', $version, PDO::PARAM_STR);
        return $stmt;
    }
}