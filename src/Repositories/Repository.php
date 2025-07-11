<?php

/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * Abstract repository + SQL-based methods for its operation.
 * |
 * |--------------------------------------------------------------------------
 */

namespace Rift\Core\Repositories;

use PDO;
use PDOStatement;
use PDOException;
use Rift\Contracts\Models\ModelInterface;
use Rift\Core\Databus\Operation;
use Rift\Core\Databus\OperationOutcome;

abstract class Repository
{
    public function __construct(
        protected PDO $pdo,
        public ModelInterface $model
    ) {}

    /**
     * Universal query execution method
     * 
     * @param PDOStatement $stmt
     * @return OperationOutcome $result
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
     * Determines the response type based on the request made
     * 
     * @param PDOStatement $stmt
     * @return OperationOutcome $result
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

    // SELECT
    private function prepareSelectOperation(PDOStatement $stmt): OperationOutcome
    {
        return Operation::success($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // INSERT
    private function prepareInsertOperation(PDOStatement $stmt): OperationOutcome
    {
        return Operation::success([
            'insert_id' => $this->pdo->lastInsertId(),
            'affected_rows' => $stmt->rowCount()
        ]);
    }

    // MODIFICATION
    private function prepareModificationOperation(PDOStatement $stmt): OperationOutcome
    {
        return Operation::success([
            'affected_rows' => $stmt->rowCount()
        ]);
    }

    // GENERIC
    private function prepareGenericOperation(PDOStatement $stmt): OperationOutcome
    {
        return Operation::success([
            'executed' => true,
            'affected_rows' => $stmt->rowCount()
        ]);
    }

    // ERRPR
    private function prepareErrorOperation(PDOStatement $stmt, PDOException $e): OperationOutcome
    {
        return Operation::error(
            Operation::HTTP_INTERNAL_SERVER_ERROR,
            'Database error: ' . $e->getMessage(),
            [
                'query' => $stmt->queryString,
                'error_info' => $stmt->errorInfo()
            ]
        );
    }
}