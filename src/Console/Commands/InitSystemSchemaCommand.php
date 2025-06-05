<?php

namespace Rift\Core\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;
use Symfony\Component\Console\Input\InputOption;

use Rift\Core\Contracts\OperationOutcome;
use Rift\Core\Console\Utils\DirectoriesUtils;
use Rift\Core\Configs\ConfigReader;

class InitSystemSchemaCommand extends Command
{
    protected static $defaultName = 'init:schema:system';

    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure()
    {
        $this->setDescription('Initializes system schema');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $systemConfiguratorClassRequest = ConfigReader::get('db.configurators.system');

        if ($systemConfiguratorClassRequest->isSuccess()) {
            $systemConfiguratorClass = $systemConfiguratorClassRequest->result;

            $output->writeln("<comment>the beginning of the deployment of the system scheme</comment>");

            $configurator = new $systemConfiguratorClass;
            $configuratorExecuteRequest = $configurator->configure();

            if ($configuratorExecuteRequest->isSuccess()) {
                $output->writeln("<info>system schema configured successful</info>");
                return Command::SUCCESS;
            } else {
                $output->writeln("<error>{$configuratorExecuteRequest->error}</error>");
                if (!empty($configuratorExecuteRequest->meta['debug'])) {
                    foreach ($configuratorExecuteRequest->meta['debug'] as $key => $value) {
                        $output->writeln("<comment>[debug] {$key}: {$value}</comment>");
                    }
                }
                return Command::FAILURE;
            }
        } else {
            $output->writeln("error read config {$systemConfiguratorClassRequest->error}");
            return Command::FAILURE;
        }
    }
}