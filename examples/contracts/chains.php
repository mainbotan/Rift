<?php


use Rift\Core\Contracts\Operation;
use Rift\Core\Contracts\OperationOutcome;

class ChainsService
{
    // FP

    /**
     * Демонстрация работы метода withMetric()
     */
    public static function demoWithMetric(): OperationOutcome
    {
        $result = Operation::success(['initial' => 'data'])
            ->withMetric('start_time', microtime(true))
            ->withMetric('service', 'user_service');
            
        // Добавляем метрику после некоторых операций
        return $result->withMetric('end_time', microtime(true));
    }

    /**
     * Демонстрация работы методов then() и map()
     */
    public static function demoThenAndMap(): OperationOutcome
    {
        return Operation::success(['id' => 1, 'name' => 'Alice'])
            ->then(function($data) {
                // Преобразуем данные и возвращаем новый OperationOutcome
                return Operation::success([
                    'user' => $data,
                    'timestamp' => time()
                ]);
            })
            ->map(function($data) {
                // Только преобразуем данные
                $data['user']['name'] = strtoupper($data['user']['name']);
                return $data;
            });
    }

    /**
     * Демонстрация работы метода catch()
     */
    public static function demoCatch(): OperationOutcome
    {
        return Operation::error(404, 'User not found')
            ->catch(function($error, $code, $meta) {
                // Логируем ошибку и возвращаем новый результат
                $meta['debug']['logged_at'] = date('Y-m-d H:i:s');
                return Operation::error(
                    $code,
                    "Handled: $error",
                    $meta
                );
            });
    }

    /**
     * Демонстрация работы метода tap()
     */
    public static function demoTap(): OperationOutcome
    {
        return Operation::success(['value' => 42])
            ->tap(function($result) {
                // Логируем без изменения результата
                error_log("Processing value: {$result['value']}");
            })
            ->map(function($result) {
                $result['value'] *= 2;
                return $result;
            })
            ->tap(function($result) {
                error_log("Doubled value: {$result['value']}");
            });
    }

    /**
     * Демонстрация работы метода ensure()
     */
    public static function demoEnsure(): OperationOutcome
    {
        return Operation::success(['age' => 17])
            ->ensure(
                fn($data) => $data['age'] >= 18,
                'User must be at least 18 years old',
                403
            );
    }

    /**
     * Демонстрация работы метода merge()
     */
    public static function demoMerge(): OperationOutcome
    {
        $userData = Operation::success(['id' => 1, 'name' => 'Alice']);
        $userStats = Operation::success(['logins' => 42, 'last_login' => '2023-01-01']);

        return $userData->merge($userStats, function($data, $stats) {
            return array_merge($data, ['stats' => $stats]);
        });
    }

    /**
     * Демонстрация работы метода toJson()
     */
    public static function demoToJson(): string
    {
        $outcome = Operation::success(
            ['id' => 1, 'name' => 'Alice'],
            ['metrics' => ['time' => 12.3]],
            ['debug' => ['request_id' => 'abc123']]
        );

        // Стандартное преобразование
        $json1 = $outcome->toJson();

        // Кастомное преобразование
        $json2 = $outcome->toJson(function(OperationOutcome $outcome) {
            return [
                'user' => $outcome->result,
                'execution_time' => $outcome->getMetric('time'),
                'success' => $outcome->isSuccess()
            ];
        });

        return $json1 . "\n\n" . $json2;
    }

    /**
     * Комплексный пример с цепочкой вызовов
     */
    public static function demoChain(): OperationOutcome
    {
        return Operation::success(['id' => 1, 'name' => ' alice '])
            ->withMetric('start_time', microtime(true))
            ->map(function($user) {
                $user['name'] = trim($user['name']);
                return $user;
            })
            ->ensure(
                fn($user) => !empty($user['name']),
                'Name cannot be empty',
                400
            )
            ->map(function($user) {
                $user['name'] = ucfirst($user['name']);
                return $user;
            })
            ->then(function($user) {
                return self::fetchUserStats($user['id'])
                    ->map(function($stats) use ($user) {
                        return array_merge($user, ['stats' => $stats]);
                    });
            })
            ->addDebugData('ahuenno', 'yes')
            ->withMetric('end_time', microtime(true));
    }

    private static function fetchUserStats(int $userId): OperationOutcome
    {
        // Имитация получения статистики
        if ($userId === 1) {
            return Operation::success([
                'logins' => 42,
                'last_login' => '2023-01-01'
            ]);
        }
        return Operation::error(404, 'Stats not found');
    }
}