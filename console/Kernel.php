<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * CLI component
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Console;

use Symfony\Component\Console\Application;
use Rift\Console\Commands\InitCommand;
use Rift\Console\Commands\InitConfigsCommand;
use Dotenv\Dotenv;
use Rift\Console\Commands\AppInfoCommand;
use Rift\Console\Commands\InitEnvCommand;
use Rift\Console\Commands\InitAppCommand;
use Rift\Console\Commands\InitSystemSchemaCommand;

class Kernel extends Application {
    public function __construct()
    {   
        parent::__construct('Rift CLI', '1.0.0');

        $dotenv = Dotenv::createImmutable(getcwd());
        $dotenv->safeLoad();

        $this->add(new InitCommand());
        $this->add(new InitAppCommand());

        $this->add(new AppInfoCommand());

        $this->add(new InitEnvCommand());
        $this->add(new InitConfigsCommand());
        $this->add(new InitSystemSchemaCommand());
    }
}