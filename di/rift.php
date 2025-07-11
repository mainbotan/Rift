<?php

use Psr\Container\ContainerInterface;
use DI\Container;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Rift\Contracts\Database\Bridge\PDO\ConnectorInterface;
use Rift\Contracts\Http\ResponseEmitter\EmitterInterface;
use Rift\Contracts\Http\Router\RouterInterface;
use Rift\Contracts\Http\RoutesBox\RoutesBoxInterface;
use Rift\Core\Database\Bridge\PDO\Connector;
use Rift\Core\Http\ResponseEmitters\CompositeEmitter;
use Rift\Core\Http\ResponseEmitters\JsonEmitter;
use Rift\Core\Http\ResponseEmitters\XmlEmitter;
use Rift\Core\Http\ResponseEmitters\TextEmitter;
use Rift\Core\Http\Router\Router;
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

    // Databse
    ConnectorInterface::class => get(Connector::class),
    Connector::class => autowire()
        ->constructorParameter('driver', $_ENV['DB_DRIVER'])
        ->constructorParameter('host', $_ENV['DB_HOST'])
        ->constructorParameter('port', (int) $_ENV['DB_PORT'])
        ->constructorParameter('username', $_ENV['DB_USER'])
        ->constructorParameter('password', $_ENV['DB_PASSWORD'])
        ->constructorParameter('defaultDatabase', $_ENV['DB_NAME']),

    /**
     * |
     * Additional services
     * |
     */

    // Monolog (PSR-3 Logger)
    LoggerInterface::class => function () {
        $logger = new Logger($_ENV['LOGGER_APP_NAME']);
        $logger->pushHandler(new StreamHandler($_ENV['LOGGER_DIR'], Logger::DEBUG));
        return $logger;
    },

    // Symfony Stopwatch
    Stopwatch::class => autowire()
];