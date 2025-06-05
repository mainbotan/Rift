<?php

namespace Rift\Core\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Rift\Core\Console\Utils\DirectoriesUtils;
use Symfony\Component\Console\Input\InputOption;

use Rift\Core\Contracts\Response;
use Rift\Core\Contracts\ResponseDTO;

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

        // Получаем директорию с шаблонами
        $stubsResult = DirectoriesUtils::getStubsDir('configs');
        if (!$stubsResult->isSuccess()) {
            $output->writeln("<error>{$stubsResult->error}</error>");
            return Command::FAILURE;
        }
        $stubsConfigsDir = $stubsResult->result;

        // Проверяем/создаем целевую директорию
        if (is_dir($projectConfigsDir)) {
            if (!$force) {
                $output->writeln("<comment>Config directory exists. Use --force to overwrite</comment>");
                return Command::FAILURE;
            }

            $cleanResult = DirectoriesUtils::cleanDirectory($projectConfigsDir);
            if (!$cleanResult->isSuccess()) {
                $output->writeln("<error>Failed to clean directory: {$cleanResult->error}</error>");
                return Command::FAILURE;
            }
            $output->writeln("<info>Cleared existing configs</info>");
        } else {
            $createResult = DirectoriesUtils::createDirectory($projectConfigsDir);
            if (!$createResult->isSuccess()) {
                $output->writeln("<error>Failed to create directory: {$createResult->error}</error>");
                return Command::FAILURE;
            }
        }

        // Копируем файлы конфигурации
        $copyResult = DirectoriesUtils::copyDirectory($stubsConfigsDir, $projectConfigsDir, $force);
        if (!$copyResult->isSuccess()) {
            $output->writeln("<error>Failed to copy configs: {$copyResult->error}</error>");
            
            if ($copyResult->code === Response::HTTP_CONFLICT) {
                $output->writeln("<info>Use --force option to overwrite existing files</info>");
            }
            
            return Command::FAILURE;
        }

        $output->writeln("<info>Configs initialized successfully</info>");
        return Command::SUCCESS;
    }
}