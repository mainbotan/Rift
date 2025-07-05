<?php
/*
 * |--------------------------------------------------------------------------
 * |
 * This file is a component of the Rift Miniframework core <v 1.0.0>
 * |
 * CLI component
 * |
 * |--------------------------------------------------------------------------
 */
namespace Rift\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Rift\Console\Utils\DirectoriesUtils;
use Rift\Console\Utils\StubsUtils;
use Symfony\Component\Console\Input\InputOption;

use Rift\Core\DataBus\Operation;
use Rift\Core\DataBus\OperationOutcome;

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