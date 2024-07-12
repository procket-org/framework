<?php

namespace Procket\Framework\Commands;

use Exception;
use Procket\Framework\Database\Migration\MigrationBaseCommand;
use Procket\Framework\Procket;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Migration command: refresh
 */
class MigrationRefreshCommand extends MigrationBaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName(
            'migration:refresh'
        )->addOption(
            'database',
            null,
            InputOption::VALUE_REQUIRED,
            'The database connection to use',
            Procket::instance()->defaultDbConnection
        )->addOption(
            'step',
            null,
            InputOption::VALUE_OPTIONAL,
            'The number of migrations to be reverted & re-run'
        )->setDescription(
            'Reset and re-run all migrations'
        );
    }

    /**
     * @inheritDoc
     * @throws Exception
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $step = $input->getOption('step') ?: 0;

        if ($step > 0) {
            $this->runRollback($input, $output);
        } else {
            $this->runReset($input, $output);
        }

        $this->runMigrate($input, $output);

        return Command::SUCCESS;
    }

    /**
     * Execute the rollback command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Exception
     * @throws ExceptionInterface
     */
    protected function runRollback(InputInterface $input, OutputInterface $output): void
    {
        $command = Procket::instance()->getConsoleApp()->find('migration:rollback');
        $arguments = array_filter([
            '--database' => $input->getOption('database'),
            '--step' => $input->getOption('step') ?: 0
        ]);

        $command->run(new ArrayInput($arguments), $output);
    }

    /**
     * Execute the reset command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Exception
     * @throws ExceptionInterface
     */
    protected function runReset(InputInterface $input, OutputInterface $output): void
    {
        $command = Procket::instance()->getConsoleApp()->find('migration:reset');
        $arguments = array_filter([
            '--database' => $input->getOption('database')
        ]);

        $command->run(new ArrayInput($arguments), $output);
    }

    /**
     * Execute the run command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Exception
     * @throws ExceptionInterface
     */
    protected function runMigrate(InputInterface $input, OutputInterface $output): void
    {
        $command = Procket::instance()->getConsoleApp()->find('migration:run');
        $arguments = array_filter([
            '--database' => $input->getOption('database')
        ]);

        $command->run(new ArrayInput($arguments), $output);
    }
}