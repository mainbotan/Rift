<?php

use Psr\Container\ContainerInterface;
use DI\Container;
use Rift\Contracts\Http\ResponseEmitter\EmitterInterface;
use Rift\Contracts\Http\Router\RouterInterface;
use Rift\Contracts\Http\RoutesBox\RoutesBoxInterface;
use Rift\Core\Http\ResponseEmitters\CompositeEmitter;
use Rift\Core\Http\ResponseEmitters\JsonEmitter;
use Rift\Core\Http\ResponseEmitters\XmlEmitter;
use Rift\Core\Http\ResponseEmitters\TextEmitter;
use Rift\Core\Http\Router\Router;
use Rift\Core\Http\RoutesBox\RoutesBox;

use function DI\autowire;
use function DI\get;

return [
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
];