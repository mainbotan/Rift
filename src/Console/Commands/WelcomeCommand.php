<?php

namespace Rift\Core\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WelcomeCommand extends Command
{
    protected static $defaultName = 'welcome';
    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("<info>welcome command</info>");

        return Command::SUCCESS;
    }

}
