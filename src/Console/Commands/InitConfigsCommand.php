<?php

namespace Rift\Core\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Rift\Core\Console\Utils\DirectoriesUtils;
use RuntimeException;
use Symfony\Component\Console\Input\InputOption;

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
        $stubsConfigsDir = DirectoriesUtils::getStubsDir('configs');

        if (!is_dir($stubsConfigsDir) || !is_readable($stubsConfigsDir)) {
            $output->writeln("<error>Stubs directory is missing or unreadable</error>");
            return Command::FAILURE;
        }

        if (is_dir($projectConfigsDir)) {
            if (!$force) {
                $output->writeln("<comment>Config directory exists. Use --force to overwrite</comment>");
                return Command::FAILURE;
            }

            if (!is_writable($projectConfigsDir)) {
                $output->writeln("<error>No write permissions for config directory</error>");
                return Command::FAILURE;
            }

            DirectoriesUtils::cleanDirectory($projectConfigsDir);
            $output->writeln("<info>Cleared existing configs</info>");
        } else {
            @mkdir($projectConfigsDir, 0755, true);
        }

        try {
            DirectoriesUtils::copyDirectory($stubsConfigsDir, $projectConfigsDir);
            $output->writeln("<info>Configs initialized successfully</info>");
            return Command::SUCCESS;
        } catch (\RuntimeException $e) {
            $output->writeln("<error>Failed: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}