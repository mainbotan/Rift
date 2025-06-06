<?php

namespace Rift\Core\Console;

use Symfony\Component\Console\Application;
use Rift\Core\Console\Commands\InitCommand;
use Rift\Core\Console\Commands\InitConfigsCommand;
use Dotenv\Dotenv;
use Rift\Core\Console\Commands\AppInfoCommand;
use Rift\Core\Console\Commands\InitEnvCommand;
use Rift\Core\Console\Commands\InitAppCommand;
use Rift\Core\Console\Commands\InitSystemSchemaCommand;

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