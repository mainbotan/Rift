<?php

namespace Rift\Core\Repositories;

use PDO;
use PDOStatement;
use PDOException;
use Rift\Core\Contracts\Operation;
use Rift\Core\Contracts\OperationOutcome;

abstract class AbstractRepository extends Operation
{
    public function __construct(
        protected PDO $pdo,
        public object $model
    ) {}

    /**
     * Универсальный метод выполнения запросов
     */
    protected function executeQuery(PDOStatement $stmt): OperationOutcome
    {
        try {
            $stmt->execute();
            
            return $this->determineOperation($stmt);
            
        } catch (PDOException $e) {
            return $this->prepareErrorOperation($stmt, $e);
        }
    }

    /**
     * Определяет тип ответа на основе выполненного запроса
     */
    private function determineOperation(PDOStatement $stmt): OperationOutcome
    {
        $sqlType = strtoupper(strtok(trim($stmt->queryString), ' '));
        
        return match($sqlType) {
            'SELECT' => $this->prepareSelectOperation($stmt),
            'INSERT' => $this->prepareInsertOperation($stmt),
            'UPDATE', 'DELETE' => $this->prepareModificationOperation($stmt),
            default => $this->prepareGenericOperation($stmt)
        };
    }

    private function prepareSelectOperation(PDOStatement $stmt): OperationOutcome
    {
        return self::success($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function prepareInsertOperation(PDOStatement $stmt): OperationOutcome
    {
        return self::success([
            'insert_id' => $this->pdo->lastInsertId(),
            'affected_rows' => $stmt->rowCount()
        ]);
    }

    private function prepareModificationOperation(PDOStatement $stmt): OperationOutcome
    {
        return self::success([
            'affected_rows' => $stmt->rowCount()
        ]);
    }

    private function prepareGenericOperation(PDOStatement $stmt): OperationOutcome
    {
        return self::success([
            'executed' => true,
            'affected_rows' => $stmt->rowCount()
        ]);
    }

    private function prepareErrorOperation(PDOStatement $stmt, PDOException $e): OperationOutcome
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