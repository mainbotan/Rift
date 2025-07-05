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

class InitCommand extends Command
{
    protected static $defaultName = 'init';

    public function __construct()
    {
        parent::__construct(self::$defaultName);
    }

    protected function configure()
    {
        $this->setDescription('Initializes the Rift CLI globally');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 1. Динамически определяем путь до бинарника
        $binaryPath = $this->resolveBinaryPath();

        if (!$binaryPath || !file_exists($binaryPath)) {
            $output->writeln('<error>Rift binary not found. Tried:</error>');
            $output->writeln('  - RIFT_BIN_PATH env variable');
            $output->writeln('  - Composer vendor dir');
            $output->writeln('  - Relative to core package');
            return Command::FAILURE;
        }

        // 2. Делаем бинарник исполняемым (если нужно)
        if (!is_executable($binaryPath)) {
            chmod($binaryPath, 0755);
            $output->writeln("<info>Made binary executable: {$binaryPath}</info>");
        }

        // 3. Предлагаем варианты установки
        $output->writeln("\n<question>Choose installation method:</question>");
        $output->writeln("  1. <comment>Symlink to /usr/local/bin</comment> (requires sudo)");
        $output->writeln("  2. <comment>User-local install (~/.local/bin)</comment> (recommended)");
        $output->writeln("  3. <comment>Docker alias</comment> (safe for containers)");
        $output->writeln("  4. <comment>Just show path</comment> (manual setup)");

        $choice = $this->askForChoice($input, $output, [1, 2, 3, 4]);

        switch ($choice) {
            case 1:
                return $this->installSystemWide($binaryPath, $output);
            case 2:
                return $this->installUserLocal($binaryPath, $output);
            case 3:
                return $this->setupDockerAlias($binaryPath, $output);
            default:
                $this->showManualInstructions($binaryPath, $output);
                return Command::SUCCESS;
        }
    }

    private function resolveBinaryPath(): ?string
    {
        // 1. Из переменной окружения
        if ($path = $_SERVER['RIFT_BIN_PATH'] ?? null) {
            return realpath($path);
        }

        // 2. Из Composer vendor dir
        if (isset($_SERVER['COMPOSER_VENDOR_DIR'])) {
            $path = $_SERVER['COMPOSER_VENDOR_DIR'] . '/rift/core/bin/rift';
            if (file_exists($path)) {
                return realpath($path);
            }
        }

        // 3. Fallback: относительно пакета
        return realpath(__DIR__ . '/../../../bin/rift');
    }

    private function installSystemWide(string $binaryPath, OutputInterface $output): int
    {
        $linkPath = '/usr/local/bin/rift';

        if (file_exists($linkPath) && !is_link($linkPath)) {
            $output->writeln("<error>Path exists and is not a symlink: {$linkPath}</error>");
            return Command::FAILURE;
        }

        if (is_link($linkPath)) {
            unlink($linkPath);
        }

        if (!symlink($binaryPath, $linkPath)) {
            $output->writeln("<error>Permission denied. Try with sudo:</error>");
            $output->writeln("<comment>  sudo ln -sf {$binaryPath} {$linkPath}</comment>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Symlink created: {$linkPath} → {$binaryPath}</info>");
        return Command::SUCCESS;
    }

    private function installUserLocal(string $binaryPath, OutputInterface $output): int
    {
        $userBin = $_SERVER['HOME'] . '/.local/bin';
        $linkPath = $userBin . '/rift';

        if (!is_dir($userBin)) {
            mkdir($userBin, 0755, true);
        }

        if (file_exists($linkPath)) {
            unlink($linkPath);
        }

        if (!symlink($binaryPath, $linkPath)) {
            $output->writeln("<error>Failed to create symlink: {$linkPath}</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Symlink created: {$linkPath} → {$binaryPath}</info>");
        $output->writeln("\n<comment>Add this to your shell config (e.g., ~/.bashrc):</comment>");
        $output->writeln("  <info>export PATH=\$PATH:~/.local/bin</info>");
        return Command::SUCCESS;
    }

    private function setupDockerAlias(string $binaryPath, OutputInterface $output): int
    {
        $output->writeln("\n<comment>Add this to your shell config (e.g., ~/.bashrc):</comment>");
        $output->writeln("  <info>alias rift='docker-compose exec php {$binaryPath}'</info>");
        return Command::SUCCESS;
    }

    private function showManualInstructions(string $binaryPath, OutputInterface $output): void
    {
        $output->writeln("\n<comment>Manual installation options:</comment>");
        $output->writeln("1. System-wide (requires sudo):");
        $output->writeln("   <info>sudo ln -sf {$binaryPath} /usr/local/bin/rift</info>");
        $output->writeln("\n2. User-local:");
        $output->writeln("   <info>mkdir -p ~/.local/bin</info>");
        $output->writeln("   <info>ln -sf {$binaryPath} ~/.local/bin/rift</info>");
        $output->writeln("   <info>echo 'export PATH=\$PATH:~/.local/bin' >> ~/.bashrc</info>");
    }

    private function askForChoice(InputInterface $input, OutputInterface $output, array $options): int
    {
        // Реализация выбора через InputInterface (можно использовать QuestionHelper)
        return 2; // По умолчанию рекомендуем user-local
    }
}