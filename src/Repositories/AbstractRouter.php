<?php

/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Abstract router - configuration of repositories.
 * |
 * |--------------------------------------------------------------------------
 */

namespace Rift\Core\Repositories;

use PDO;
use PDOException;
use Rift\Core\Contracts\Operation;
use Rift\Core\Contracts\OperationOutcome;
use Rift\Core\Database\Connect;

abstract class AbstractRouter extends Operation
{
    private array $connections = [];
    protected array $repositories = [];
    protected string $schema;
    
    public function getRepository(string $key): OperationOutcome
    {
        if (!isset($this->repositories[$key])) {
            return self::error(self::HTTP_NOT_FOUND, "Repository {$key} not found");
        }

        $pdoOperation = $this->getConnection();
        if ($pdoOperation->code !== self::HTTP_OK) {
            return $pdoOperation;
        }

        $config = $this->repositories[$key];
        return self::success(new $config['class']($pdoOperation->result, new($config['model'])));
    }

    private function getConnection(): OperationOutcome
    {
        // Проверяем живое ли соединение
        if (isset($this->connections[$this->schema])) {
            try {
                $this->connections[$this->schema]->query('SELECT 1');
                return self::success($this->connections[$this->schema]);
            } catch (PDOException $e) {
                unset($this->connections[$this->schema]);
            }
        }

        // Создаем новое
        $response = Connect::getPdoForSchema($this->schema);
        if ($response->code === self::HTTP_OK) {
            $this->connections[$this->schema] = $response->result;
        }

        return $response;
    }
}