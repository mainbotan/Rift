<?php

namespace Rift\Core\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends Command
{
    protected static $defaultName = 'init';
    public function __construct()
    {
        parent::__construct('init');
    }

    protected function configure()
    {
        $this->setDescription('Initializes the Rift CLI globally');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $binaryPath = realpath(__DIR__ . '/../../../bin/rift'); // путь к ядру core/bin/rift
        $linkPath = '/usr/local/bin/rift';

        if (!$binaryPath || !file_exists($binaryPath)) {
            $output->writeln("<error>File bin/rift not found at expected path.</error>");
            return Command::FAILURE;
        }

        if (!is_executable($binaryPath)) {
            chmod($binaryPath, 0755);
            $output->writeln("<info>Made bin/rift executable.</info>");
        }

        if (file_exists($linkPath) || is_link($linkPath)) {
            unlink($linkPath);
            $output->writeln("<comment>Removed existing /usr/local/bin/rift.</comment>");
        }

        try {
            symlink($binaryPath, $linkPath);
            $output->writeln("<info>rift CLI installed globally. You can now run `rift` from anywhere.</info>");
        } catch (\Exception $e) {
            $output->writeln("<error>Failed to link rift globally: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

}
