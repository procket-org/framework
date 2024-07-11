<?php

namespace Pocket\Framework\Commands;

use Pocket\Framework\Database\Migration\MigrationBaseCommand;
use Pocket\Framework\Pocket;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Migration command: run
 */
class MigrationRunCommand extends MigrationBaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName(
            'migration:run'
        )->addOption(
            'database',
            null,
            InputOption::VALUE_REQUIRED,
            'The database connection to use',
            Pocket::instance()->defaultDbConnection
        )->addOption(
            'pretend',
            null,
            InputOption::VALUE_NONE,
            'Dump the SQL queries that would be run'
        )->addOption(
            'step',
            null,
            InputOption::VALUE_OPTIONAL,
            'Force the migrations to be run so they can be rolled back individually'
        )->setDescription(
            'Run the database migrations'
        );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $database = $input->getOption('database');
        $pretend = $input->getOption('pretend');
        $step = $input->getOption('step');

        $migrator = $this->getMigrator();
        $migrator->usingConnection($database, function () use ($migrator, $output, $pretend, $step) {
            $migrator->setOutput($output)->run([$this->migrationsPath], [
                'pretend' => $pretend,
                'step' => (int)$step
            ]);
        });

        return Command::SUCCESS;
    }
}