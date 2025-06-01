<?php

namespace Rift\Core\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends Command {
    protected static $defaultName = 'init';

    protected function configure()
    {
        $this->setDescription('initialization new project');
        
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<comment>ye boi</comment>');
        return Command::SUCCESS;
    }
}