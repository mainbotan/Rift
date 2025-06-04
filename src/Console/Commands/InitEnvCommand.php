<?php

namespace Rift\Core\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Rift\Core\Console\Utils\DirectoriesUtils;
use RuntimeException;
use Symfony\Component\Console\Input\InputOption;

class InitEnvCommand extends Command
{
    protected static $defaultName = 'init:env';

    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure()
    {
        $this->setDescription('Initializes .env file')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Overwrite existing .env file'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = $input->getOption('force');
        $projectRoot = getcwd();
        
        try {
            $stubsEnvDir = DirectoriesUtils::getStubsDir('env');
        } catch (RuntimeException $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        $stubsEnvFile = $stubsEnvDir . '/.env';

        if (!DirectoriesUtils::fileExists($stubsEnvFile)) {
            $output->writeln("<error>.env file from stubs not found at: {$stubsEnvFile}</error>");
            return Command::FAILURE;
        }

        $targetEnvPath = $projectRoot . '/.env';

        try {
            DirectoriesUtils::copyFile($stubsEnvFile, $targetEnvPath, $force);
            $output->writeln("<info>.env file created successfully at: {$targetEnvPath}</info>");
            return Command::SUCCESS;
        } catch (RuntimeException $e) {
            $output->writeln([
                "<error>Failed to initialize .env file:</error>",
                "<comment>{$e->getMessage()}</comment>"
            ]);
            
            if (file_exists($targetEnvPath) && !$force) {
                $output->writeln("<info>Use --force option to overwrite existing file</info>");
            }
            
            return Command::FAILURE;
        }
    }
}