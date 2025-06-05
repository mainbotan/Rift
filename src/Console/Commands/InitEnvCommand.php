<?php

namespace Rift\Core\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Rift\Core\Console\Utils\DirectoriesUtils;
use Symfony\Component\Console\Input\InputOption;

use Rift\Core\Contracts\Response;
use Rift\Core\Contracts\ResponseDTO;

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
        $targetEnvPath = $projectRoot . '/.env';

        // Получаем директорию с шаблонами
        $stubsResult = DirectoriesUtils::getStubsDir('env');
        if (!$stubsResult->isSuccess()) {
            $output->writeln("<error>{$stubsResult->error}</error>");
            return Command::FAILURE;
        }
        
        $stubsEnvFile = $stubsResult->result . '/.env';

        // Проверяем существование шаблона
        $fileExists = DirectoriesUtils::fileExists($stubsEnvFile);
        if (!$fileExists->isSuccess() || !$fileExists->result) {
            $output->writeln("<error>.env file from stubs not found at: {$stubsEnvFile}</error>");
            return Command::FAILURE;
        }

        // Копируем файл
        $copyResult = DirectoriesUtils::copyFile($stubsEnvFile, $targetEnvPath, $force);
        if (!$copyResult->isSuccess()) {
            $output->writeln([
                "<error>Failed to initialize .env file:</error>",
                "<comment>{$copyResult->error}</comment>"
            ]);
            
            if ($copyResult->code === Response::HTTP_CONFLICT) {
                $output->writeln("<info>Use --force option to overwrite existing file</info>");
            }
            
            return Command::FAILURE;
        }

        $output->writeln("<info>.env file created successfully at: {$targetEnvPath}</info>");
        return Command::SUCCESS;
    }
}