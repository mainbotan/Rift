<?php
namespace Rift\Core\Repositories;

use PDO;
use PDOException;
use Rift\Core\Contracts\Response;
use Rift\Core\Contracts\ResponseDTO;
use Rift\Core\Database\Connect;

abstract class AbstractRouter extends Response
{
    private array $connections = [];
    protected array $repositories = [];
    protected string $schema;
    
    public function getRepository(string $key): ResponseDTO
    {
        if (!isset($this->repositories[$key])) {
            return self::error(self::HTTP_NOT_FOUND, "Repository {$key} not found");
        }

        $pdoResponse = $this->getConnection();
        if ($pdoResponse->code !== self::HTTP_OK) {
            return $pdoResponse;
        }

        $config = $this->repositories[$key];
        return self::success(new $config['class']($pdoResponse->result, new($config['model'])));
    }

    private function getConnection(): ResponseDTO
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