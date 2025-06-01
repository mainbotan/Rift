<?php

namespace Rift\Core\Repositories;

use PDO;
use PDOStatement;
use PDOException;
use Rift\Core\Contracts\Response;
use Rift\Core\Contracts\ResponseDTO;

abstract class AbstractRepository extends Response
{
    public function __construct(
        protected PDO $pdo,
        protected object $model
    ) {}

    /**
     * Универсальный метод выполнения запросов
     */
    protected function executeQuery(PDOStatement $stmt): ResponseDTO
    {
        try {
            $stmt->execute();
            
            return $this->determineResponse($stmt);
            
        } catch (PDOException $e) {
            return $this->prepareErrorResponse($stmt, $e);
        }
    }

    /**
     * Определяет тип ответа на основе выполненного запроса
     */
    private function determineResponse(PDOStatement $stmt): ResponseDTO
    {
        $sqlType = strtoupper(strtok(trim($stmt->queryString), ' '));
        
        return match($sqlType) {
            'SELECT' => $this->prepareSelectResponse($stmt),
            'INSERT' => $this->prepareInsertResponse($stmt),
            'UPDATE', 'DELETE' => $this->prepareModificationResponse($stmt),
            default => $this->prepareGenericResponse($stmt)
        };
    }

    private function prepareSelectResponse(PDOStatement $stmt): ResponseDTO
    {
        return self::success($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function prepareInsertResponse(PDOStatement $stmt): ResponseDTO
    {
        return self::success([
            'insert_id' => $this->pdo->lastInsertId(),
            'affected_rows' => $stmt->rowCount()
        ]);
    }

    private function prepareModificationResponse(PDOStatement $stmt): ResponseDTO
    {
        return self::success([
            'affected_rows' => $stmt->rowCount()
        ]);
    }

    private function prepareGenericResponse(PDOStatement $stmt): ResponseDTO
    {
        return self::success([
            'executed' => true,
            'affected_rows' => $stmt->rowCount()
        ]);
    }

    private function prepareErrorResponse(PDOStatement $stmt, PDOException $e): ResponseDTO
    {
        return self::error(
            self::HTTP_INTERNAL_SERVER_ERROR,
            'Database error: ' . $e->getMessage(),
            [
                'query' => $stmt->queryString,
                'error_info' => $stmt->errorInfo()
            ]
        );
    }
}