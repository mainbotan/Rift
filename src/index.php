<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Rift\Core\Database\Connect;

// Читаем .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Модели

// use Rift\Models\System\Tenants;
// $result = Tenants::getTableName();

// Запуск схем

// use Rift\Configurators\AppSystemConfigurator;
// $result = AppSystemConfigurator::configure();

// use Rift\Configurators\AppTenantConfigurator;
// $result = AppTenantConfigurator::forTenant('982')->configure();

// Репозитории

use Rift\Repositories\System\Router;
$systemRouter = new Router();
$tenantsRepository = $systemRouter->getRepository('tenants.repo');
if ($tenantsRepository->code === 200) {
    $tenantsRepository = $tenantsRepository->result;

    // Запрос к репозиторию
    $result = $tenantsRepository->createTenant([
        'name' => 'huila',
        'email' => 'test@gmail.com',
        'password' => 123456   
    ]);
}

echo "<pre>";
var_dump($result);
echo "</pre>";