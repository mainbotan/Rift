<?php

use Rift\Core\Contracts\Operation;
use Rift\Core\Contracts\OperationOutcome;

class SomeService 
{
    /**
     * Базовый пример успешной операции
     */
    public static function simpleSuccess(): OperationOutcome
    {
        $result = ['data' => 'Successful operation'];
        return Operation::success($result);
    }

    /**
     * Успешная операция с метриками
     */
    public static function successWithMetrics(): OperationOutcome
    {
        $result = ['user_id' => 123, 'name' => 'John Doe'];
        
        $metrics = [
            'execution_time_ms' => 45.2,
            'memory_usage_mb' => 12.7,
            'database_queries' => 3
        ];
        
        return Operation::success($result, $metrics);
    }

    /**
     * Успешная операция с дебаг-информацией
     */
    public static function successWithDebug(): OperationOutcome
    {
        $result = ['status' => 'processed'];
        
        $debug = [
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3),
            'request_id' => uniqid()
        ];
        
        return Operation::success($result, null, $debug);
    }

    /**
     * Простая ошибка с кодом HTTP 400
     */
    public static function simpleError(): OperationOutcome
    {
        return Operation::error(
            Operation::HTTP_BAD_REQUEST,
            'Invalid input parameters'
        );
    }

    /**
     * Ошибка с дебаг-информацией
     */
    public static function errorWithDebug(): OperationOutcome
    {
        return Operation::error(
            Operation::HTTP_NOT_FOUND,
            'User not found',
            [
                'searched_id' => 999,
                'available_ids' => [1, 2, 3],
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)
            ]
        );
    }

    /**
     * Комплексный пример с обработкой бизнес-логики
     */
    public static function processUser(int $userId): OperationOutcome
    {
        // Валидация
        if ($userId <= 0) {
            return Operation::error(
                Operation::HTTP_BAD_REQUEST,
                'Invalid user ID',
                ['received_id' => $userId]
            );
        }

        // Бизнес-логика
        try {
            $user = self::fetchUserFromDb($userId);
            
            if (!$user) {
                return Operation::error(
                    Operation::HTTP_NOT_FOUND,
                    'User not found in database',
                    ['searched_id' => $userId]
                );
            }

            $processedUser = self::processUserData($user);
            
            $metrics = [
                'db_query_time' => 12.5,
                'processing_time' => 23.1
            ];
            
            $debug = [
                'original_data' => $user,
                'processed_at' => date('Y-m-d H:i:s')
            ];
            
            return Operation::success($processedUser, $metrics, $debug);
            
        } catch (\Exception $e) {
            return Operation::error(
                Operation::HTTP_INTERNAL_SERVER_ERROR,
                'Processing failed',
                [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            );
        }
    }

    private static function fetchUserFromDb(int $userId): ?array
    {
        // Имитация запроса к БД
        if ($userId === 1) {
            return ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'];
        }
        return null;
    }

    private static function processUserData(array $user): array
    {
        // Имитация обработки данных
        return [
            'id' => $user['id'],
            'username' => strtolower($user['name']),
            'email_verified' => true
        ];
    }
}