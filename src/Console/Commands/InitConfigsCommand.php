<?php

namespace Rift\Core\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Rift\Core\Console\Utils\DirectoriesUtils;
use Rift\Core\Console\Utils\StubsUtils;
use Symfony\Component\Console\Input\InputOption;

use Rift\Core\DataBus\Operation;
use Rift\Core\DataBus\OperationOutcome;

class InitConfigsCommand extends Command
{
    protected static $defaultName = 'init:configs';

    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure()
    {
        $this->setDescription('Initializes Rift configuration files')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Overwrite existing config files'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = $input->getOption('force');
        $projectRoot = getcwd();
        $projectConfigsDir = "{$projectRoot}/configs/";
        
        $stubsDir = dirname(__DIR__, 3) . StubsUtils::$stubsAppDir . '/configs';

        $copyRequest = StubsUtils::copyStubDirectory($stubsDir, $projectConfigsDir, $force);
        if (!$copyRequest->isSuccess()) {
            $output->writeln("<error>{$copyRequest->code}: {$copyRequest->error}</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Configs initialized successfully</info>");
        return Command::SUCCESS;
    }
}