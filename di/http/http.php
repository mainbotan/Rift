<?php
/*
 * |--------------------------------------------------------------------------
 * | DI Configuration part
 * |--------------------------------------------------------------------------
 */

use function DI\autowire;
use function DI\create;
use function DI\get;

use Psr\Container\ContainerInterface;
use Rift\Contracts\Http\Router\RouterInterface;
use Rift\Core\Http\ResponseEmitters\{
    EmitterInterface,
    CompositeEmitter,
    JsonEmitter,
    XmlEmitter,
    TextEmitter
};
use Rift\Core\Http\Router;

return [
    
];