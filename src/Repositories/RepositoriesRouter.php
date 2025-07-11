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
use Rift\Core\Database\Bridge\PDO\Connector;
use Rift\Core\Databus\Operation;
use Rift\Core\Databus\OperationOutcome;
use Rift\Core\Database\Connect;

abstract class RepositoriesRouter extends Operation
{
    private array $connections = [];
    protected string $schema;

    public function __construct()
    {
        
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
        $response = Connector::getPdoForSchema($this->schema);
        if ($response->code === self::HTTP_OK) {
            $this->connections[$this->schema] = $response->result;
        }

        return $response;
    }
}