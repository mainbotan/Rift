<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * DI Configuration.
 * |
 * |--------------------------------------------------------------------------
 */

use Psr\Container\ContainerInterface;
use DI\Container;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Predis\Client;
use Predis\ClientInterface;
use Psr\Log\LoggerInterface;
use Rift\Contracts\Database\Bridge\PDO\ConnectorInterface;
use Rift\Contracts\Database\Configurators\ConfiguratorInterface;
use Rift\Contracts\Http\RoutesBox\RoutesBoxInterface;
use Rift\Core\Cache\Redis\RedisCacheService;
use Rift\Core\Database\Bridge\PDO\Connector;
use Rift\Core\Database\Configurators\Configurator;
use Rift\Core\Http\ResponseEmitters\CompositeEmitter;
use Rift\Core\Http\ResponseEmitters\JsonEmitter;
use Rift\Core\Http\ResponseEmitters\XmlEmitter;
use Rift\Core\Http\ResponseEmitters\TextEmitter;
use Rift\Core\Http\Router\Router;
use Rift\Crypto\EncryptionManager;
use Rift\Crypto\HashManager;
use Rift\Crypto\JwtManager;
use Rift\Crypto\UidManager;
use Rift\Metrics\Stopwatch\StopwatchManager;
use Symfony\Component\Stopwatch\Stopwatch;

use function DI\autowire;
use function DI\get;

return [
    /**
     * |
     * Critical dependencies
     * | 
     */

    // Response Emitters
    CompositeEmitter::class => autowire()
        ->constructorParameter('emitters', [
            get(JsonEmitter::class),
            get(XmlEmitter::class),
            get(TextEmitter::class),
        ]),
    JsonEmitter::class => autowire(),
    XmlEmitter::class => autowire(),
    TextEmitter::class => autowire(),

    // Router
    Router::class => autowire()
        ->constructorParameter('routesBox', get(RoutesBoxInterface::class))
        ->constructorParameter('container', get(ContainerInterface::class)),

    // PSR-11 Container
    ContainerInterface::class => function (Container $container) {
        return $container;
    },

    // PDO Connector
    ConnectorInterface::class => get(Connector::class),
    Connector::class => autowire()
        ->constructorParameter('driver', $_ENV['DB_DRIVER'])
        ->constructorParameter('host', $_ENV['DB_HOST'])
        ->constructorParameter('port', (int) $_ENV['DB_PORT'])
        ->constructorParameter('username', $_ENV['DB_USER'])
        ->constructorParameter('password', $_ENV['DB_PASSWORD'])
        ->constructorParameter('defaultDatabase', $_ENV['DB_NAME']),

    // Schemas configurator
    ConfiguratorInterface::class => get(Configurator::class),
    Configurator::class => autowire(),

    /**
     * |
     * Additional services
     * |
     */

    // Redis
    RedisCacheService::class => autowire(),
    ClientInterface::class => get(Client::class),

    Client::class => autowire()
        ->constructorParameter('parameters', [
            'scheme' => 'tcp',
            'host' => $_ENV['REDIS_HOST'] ?? 'redis',
            'port' => (int) ($_ENV['REDIS_PORT'] ?? 6379),
            'password' => $_ENV['REDIS_PASSWORD'] ?? null,
            'database' => (int) ($_ENV['REDIS_DATABASE'] ?? 0),
            'timeout' => (float) ($_ENV['REDIS_TIMEOUT'] ?? 1.0),
        ])
        ->constructorParameter('options', null),

    // Symfony Stopwatch Manager
    StopwatchManager::class => autowire(),

    // Encryption Manager
    EncryptionManager::class => autowire()
        ->constructorParameter('cipher', 'AES-256-CBC')
        ->constructorParameter('keyDerivation', 'sha256')
        ->constructorParameter('key', $_ENV['ENCRYPTION_MANAGER_KEY']),

    // JWT Manager
    JwtManager::class => autowire()
        ->constructorParameter('secretKey', $_ENV['JWT_MANAGER_KEY'])
        ->constructorParameter('defaultTtl', 3600)
        ->constructorParameter('algorithm', 'HS256'),

    // UID Manager
    UidManager::class => autowire(),
    
    // Hash Manager
    HashManager::class => autowire()
        ->constructorParameter('algorithm', PASSWORD_ARGON2ID)
        ->constructorParameter('options', [
            'memory_cost' => 16384,
            'time_cost'   => 3,
            'threads'     => 1
        ]),

    // Monolog (PSR-3 Logger)
    LoggerInterface::class => function () {
        $logger = new Logger($_ENV['LOGGER_APP_NAME']);
        $logger->pushHandler(new StreamHandler($_ENV['LOGGER_DIR'], Logger::DEBUG));
        return $logger;
    },

    // Symfony Stopwatch
    Stopwatch::class => autowire()
];