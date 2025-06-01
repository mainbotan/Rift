<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Rift\Core\Database\Connect;

// Читаем .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Модели

// use Rift\Models\System\Tenants;
// $result = Tenants::getMigrationSQL();

// $testData = [
//     'id' => 100,
//     'email' => 'invalid_email',
//     'password' => '12345678',
//     'status' => 'active' 
// ];
// $result = Tenants::validateField('password', '12345');

// Запуск схем

use Rift\Configurators\AppSystemConfigurator;
$result = AppSystemConfigurator::configure();

// use Rift\Configurators\AppTenantConfigurator;

// $result = AppTenantConfigurator::forTenant('100')->configure();

// Системные репозитории

// use Rift\Repositories\System\Router;
// $systemRouter = new Router();
// $tenantsRepository = $systemRouter->getRepository('tenants.repo');

// if ($tenantsRepository->isSuccess()) {
//     $tenantsRepository = $tenantsRepository->result;

//     // Запрос к репозиторию

//     $result = $tenantsRepository->createTenant([
//         'name' => 'test',
//         'email' => 'createTest@gmail.com',
//         'password' => 123456   
//     ]);

//     // $result = $tenantsRepository->selectAll(10, 0);

//     // $result = $tenantsRepository->selectById(1);
// }


// Репозитории тенанта

// use Rift\Repositories\Tenant\Router;
// $tenantRouter = Router::forTenant('982');
// $usersRepoRequest = $tenantRouter->getRepository('users.repo');
// if ($usersRepoRequest->isSuccess()) {
//     $usersRepo = $usersRepoRequest->result;
//     $result = $usersRepo->createUser([
//         'name' => 'Пидрила',
//         'password' => '123456',
//         'role' => 'worker'
//     ]);
// }

// Crypto
// use Rift\Core\Crypto\CryptoFactory;
// $cryptoConfig = require 'config/crypto.php';
// $cryptoFactory = new CryptoFactory($cryptoConfig);

// $result = $cryptoFactory->getJwt()->encode([
//     'tenantId' => '982'
// ], 3600);

echo "<pre>";
var_dump($result);
echo "</pre>";