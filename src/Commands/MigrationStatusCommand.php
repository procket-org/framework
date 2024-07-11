<?php

namespace Pocket\Framework\Commands;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Collection;
use Pocket\Framework\Database\Migration\MigrationBaseCommand;
use Pocket\Framework\Pocket;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Migration command: status
 */
class MigrationStatusCommand extends MigrationBaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName(
            'migration:status'
        )->addOption(
            'database',
            null,
            InputOption::VALUE_REQUIRED,
            'The database connection to use',
            Pocket::instance()->defaultDbConnection
        )->setDescription(
            'Show the status of each migration'
        );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $database = $input->getOption('database');

        $migrator = $this->getMigrator();
        $migrator->usingConnection($database, function () use ($migrator, $output) {
            if (!$migrator->repositoryExists()) {
                $output->writeln('<error>Migration repository table not found</error>');
                return;
            }

            $ran = $migrator->getRepository()->getRan();

            $batches = $migrator->getRepository()->getMigrationBatches();

            if (count($migrations = $this->getStatusFor($migrator, $ran, $batches)) > 0) {
                $table = new Table($output);
                $table->setHeaders(['Ran?', 'Migration', 'Batch'])->setRows($migrations->toArray());
                $table->render();
            } else {
                $output->writeln('<error>No migrations found</error>');
            }
        });

        return Command::SUCCESS;
    }

    /**
     * Get status for completed migrations
     *
     * @param Migrator $migrator Migrator
     * @param array $ran completed migrations
     * @param array $batches batch numbers
     * @return Collection
     */
    protected function getStatusFor(Migrator $migrator, array $ran, array $batches): Collection
    {
        $allMigrationFiles = $migrator->getMigrationFiles([$this->migrationsPath]);

        return Collection::make($allMigrationFiles)
            ->map(function ($migration) use ($migrator, $ran, $batches) {
                $migrationName = $migrator->getMigrationName($migration);

                return in_array($migrationName, $ran)
                    ? ['<info>Yes</info>', $migrationName, $batches[$migrationName]]
                    : ['<fg=red>No</fg=red>', $migrationName];
            });
    }
}