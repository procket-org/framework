<?php

namespace Pocket\Framework\Commands;

use Pocket\Framework\Database\Migration\MigrationBaseCommand;
use Pocket\Framework\Pocket;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Migration command: reset
 */
class MigrationResetCommand extends MigrationBaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName(
            'migration:reset'
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
        )->setDescription(
            'Rollback all database migrations'
        );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $database = $input->getOption('database');
        $pretend = $input->getOption('pretend');

        $migrator = $this->getMigrator();
        $ok = $migrator->usingConnection($database, function () use ($migrator, $output, $pretend) {
            if (!$migrator->repositoryExists()) {
                $output->writeln('<error>Migration repository table not found</error>');
                return false;
            }

            $migrator->setOutput($output)->reset(
                [$this->migrationsPath], $pretend
            );
            return true;
        });

        return $ok ? Command::SUCCESS : Command::FAILURE;
    }
}