<?php

namespace Pocket\Framework\Commands;

use Exception;
use Illuminate\Database\Console\Migrations\TableGuesser;
use Illuminate\Support\Str;
use Pocket\Framework\Database\Migration\MigrationBaseCommand;
use Pocket\Framework\Database\Migration\MigrationCreator;
use Pocket\Framework\Pocket;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Migration command: make
 */
class MigrationMakeCommand extends MigrationBaseCommand
{
    /**
     * Migration class stub
     * @var string|null
     */
    public ?string $stub = null;

    /**
     * Migration create stub
     * @var string|null
     */
    public ?string $createStub = null;

    /**
     * Migration update stub
     * @var string|null
     */
    public ?string $updateStub = null;

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName(
            'migration:make'
        )->addArgument(
            'name',
            InputArgument::REQUIRED,
            'Name this migration'
        )->addOption(
            'create',
            null,
            InputOption::VALUE_REQUIRED,
            'The table to be created'
        )->addOption(
            'table',
            null,
            InputOption::VALUE_REQUIRED,
            'The table to migrate'
        )->setDescription(
            'Create a new migration file'
        );
    }

    /**
     * Get migration creator options
     *
     * @return array
     */
    protected function getMigrationCreatorOptions(): array
    {
        $options = [];

        if (!is_null($this->stub)) {
            $options['stub'] = $this->stub;
        }
        if (!is_null($this->createStub)) {
            $options['createStub'] = $this->createStub;
        }
        if (!is_null($this->updateStub)) {
            $options['updateStub'] = $this->updateStub;
        }

        return $options;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = Str::snake(trim($input->getArgument('name')));
        $table = $input->getOption('table');
        $create = $input->getOption('create') ?: false;

        if (!$table && is_string($create)) {
            $table = $create;
            $create = true;
        }
        if (!$table) {
            [$table, $create] = TableGuesser::guess($name);
        }

        if (!$this->migrationsPath) {
            return Command::FAILURE;
        }

        $migrationCreator = new MigrationCreator(
            Pocket::instance()->getFilesystem(),
            $this->getMigrationCreatorOptions()
        );
        $createdFile = $migrationCreator->create(
            $name, $this->migrationsPath, $table, $create
        );
        if (!file_exists($createdFile)) {
            $output->writeln("<error>Cannot create the migration file</error>");
            return Command::FAILURE;
        }

        $filename = pathinfo($createdFile, PATHINFO_FILENAME);
        $output->writeln("<info>Created Migration:</info> $filename");

        return Command::SUCCESS;
    }
}