<?php

namespace Rift\Core\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Rift\Core\Console\Utils\DirectoriesUtils;
use Rift\Core\Console\Utils\StubsUtils;
use Symfony\Component\Console\Input\InputOption;

use Rift\Core\Contracts\Operation;
use Rift\Core\Contracts\OperationOutcome;

class InitAppCommand extends Command
{
    protected static $defaultName = 'init:app';

    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure()
    {
        $this->setDescription('Initializes new application')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Overwrite existing app files'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = StubsUtils::initProjectStructure(
            targetRoot: getcwd(),
            overwrite: $input->getOption('force')
        );

        if (!$result->isSuccess()) {
            foreach ($result->result['errors'] ?? [] as $error) {
                $output->writeln("<error>{$error}</error>");
            }
            return Command::FAILURE;
        }

        foreach ($result->result['created'] ?? [] as $file) {
            $output->writeln("<info>Created: {$file}</info>");
        }
        $output->writeln("<info>New app initialized successfully</info>");
        return Command::SUCCESS;
    }
}