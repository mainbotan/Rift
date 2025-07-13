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
use Psr\Log\LoggerInterface;
use Rift\Contracts\Database\Bridge\PDO\ConnectorInterface;
use Rift\Contracts\Database\Configurators\ConfiguratorInterface;
use Rift\Contracts\Http\RoutesBox\RoutesBoxInterface;
use Rift\Core\Database\Bridge\PDO\Connector;
use Rift\Core\Database\Configurators\Configurator;
use Rift\Core\Http\ResponseEmitters\CompositeEmitter;
use Rift\Core\Http\ResponseEmitters\JsonEmitter;
use Rift\Core\Http\ResponseEmitters\XmlEmitter;
use Rift\Core\Http\ResponseEmitters\TextEmitter;
use Rift\Core\Http\Router\Router;
use Rift\Crypto\HashManager;
use Rift\Crypto\JwtManager;
use Rift\Crypto\UidManager;
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

    // JWT Manager
    JwtManager::class => autowire()
        ->constructorParameter('secretKey', $_ENV['JWT_MANAGER_KEY'])
        ->constructorParameter('defaultTtl', 3600)
        ->constructorParameter('algorithm', 'HS256'),

    // UID Manager
    UidManager::class => autowire(),

    // Hash Manager
    HashManager::class => autowire()
        ->constructorParameter('key', $_ENV['HASH_MANAGER_KEY'])
        ->constructorParameter('options', [
            'memory_cost' => 1 << 16, 
            'time_cost'   => 4,
            'threads'     => 2
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