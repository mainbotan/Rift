<?php

namespace Rift\Core\Console;

use Symfony\Component\Console\Application;
use Rift\Core\Console\Commands\InitCommand;
use Rift\Core\Console\Commands\WelcomeCommand;

class Kernel extends Application {
    public function __construct()
    {   
        parent::__construct('Rift CLI', '1.0.0');

        $this->add(new InitCommand());
        $this->add(new WelcomeCommand());
    }
}