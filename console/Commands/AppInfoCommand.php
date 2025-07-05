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

use Rift\Core\Configs\ConfigReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppInfoCommand extends Command
{
    protected static $defaultName = 'app:info';

    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure()
    {
        $this->setDescription('Application info');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configsDir = $_ENV['APP_ROOT'] . '/configs';

        ConfigReader::setBasePath($configsDir);
        $appInfoRequest = ConfigReader::getMany([
            'app.mode', 'app.version', 'app.name'
        ]);

        if (!$appInfoRequest->isSuccess()) {
            $output->writeln("<error>Failed load app config: {$appInfoRequest->error}</error>");
        }

        $appInfo = $appInfoRequest->result;
        $output->writeln("
        <info>| Application info</info>
        | 
        | <comment>name: {$appInfo['app.name']}</comment>
        | <comment>version: {$appInfo['app.version']}</comment>
        | <comment>mode: {$appInfo['app.mode']}</comment>
        ");
        return Command::SUCCESS;
    }
}