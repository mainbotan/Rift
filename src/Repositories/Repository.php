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


    /**
     * Generates a parameterized INSERT query based on provided data
     * 
     * @param array $data Associative array where keys are column names and values are data to insert
     * @return OperationOutcome Returns prepared PDO statement wrapped in OperationOutcome
     */
    protected function buildInsertQuery(array $data): OperationOutcome 
    {
        // Get table name from model (assuming getTableName() exists)
        $table = $this->model::getTableName();

        // Filter out null values if needed (remove this line if you want to insert NULLs)
        $filteredData = array_filter($data, fn($value) => $value !== null);
        
        // Check we have data to insert
        if (empty($filteredData)) {
            return Operation::error(
                Operation::HTTP_BAD_REQUEST, 
                "No valid data provided for insertion."
            );
        }

        // Prepare column and placeholder lists
        $columns = array_keys($filteredData);
        $placeholders = array_map(fn($col) => ":{$col}", $columns);

        // Build SQL query
        $columnsClause = implode(', ', $columns);
        $placeholdersClause = implode(', ', $placeholders);
        $sql = "INSERT INTO {$table} ({$columnsClause}) VALUES ({$placeholdersClause})";

        // Prepare statement
        try {
            $stmt = $this->pdo->prepare($sql);
            
            // Bind all values with proper typing
            foreach ($filteredData as $column => $value) {
                $stmt->bindValue(":{$column}", $value, $this->getParamType($value));
            }

            return Operation::success($stmt);
            
        } catch (\PDOException $e) {
            return Operation::error(
                Operation::HTTP_INTERNAL_SERVER_ERROR,
                "Failed to prepare INSERT statement: " . $e->getMessage()
            );
        }
    }

    /**
     * Generates an UPDATE request based on the data.
     * 
     * @param array $data Data to update (keys = field names).
     * @param array $where The WHERE conditions
     */
    protected function buildUpdateQuery(array $data, array $where): OperationOutcome 
    {
        $table = $this->model::getTableName();

        $filteredData = array_filter($data, fn($value) => $value !== null);
        if (empty($filteredData)) {
            return Operation::error(Operation::HTTP_BAD_REQUEST, "No fields to update.");
        }
        $setParts = [];
        foreach (array_keys($filteredData) as $field) {
            $setParts[] = "{$field} = :{$field}";
        }
        $setClause = implode(', ', $setParts);

        $whereParts = [];
        foreach ($where as $field => $value) {
            $whereParts[] = "{$field} = :where_{$field}";
        }
        $whereClause = implode(' AND ', $whereParts);

        $sql = "UPDATE {$table} SET {$setClause} WHERE {$whereClause}";
        $stmt = $this->pdo->prepare($sql);

        foreach ($filteredData as $field => $value) {
            $stmt->bindValue(":{$field}", $value, $this->getParamType($value));
        }

        foreach ($where as $field => $value) {
            $stmt->bindValue(":where_{$field}", $value, $this->getParamType($value));
        }

        return Operation::success($stmt);
    }

    protected function getParamType($value): int 
    {
        return match (true) {
            is_int($value) => \PDO::PARAM_INT,
            is_bool($value) => \PDO::PARAM_BOOL,
            is_null($value) => \PDO::PARAM_NULL,
            default => \PDO::PARAM_STR,
        };
    }

}