<?php

namespace Procket\Framework\Database\Migration;

use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\Migrator;
use Procket\Framework\ClassPropertiesAware;
use Procket\Framework\Procket;
use RuntimeException;
use Symfony\Component\Console\Command\Command;

/**
 * Migration command base class
 */
abstract class MigrationBaseCommand extends Command
{
    use ClassPropertiesAware;

    /**
     * Migration table name
     * @var string
     */
    public string $repositoryTable = 'migrations';

    /**
     * Migration class files path
     * @var string
     */
    public string $migrationsPath = DATABASE_PATH . '/migrations';

    /**
     * Constructor
     *
     * @param array $options class public properties
     */
    public function __construct(array $options = [])
    {
        parent::__construct(data_get($options, 'name'));

        $this->setClassOptions($options);

        if (!Procket::instance()->ensureDirectory($this->migrationsPath)) {
            throw new RuntimeException(sprintf(
                "<error>Directory %s does not exist and failed to create</error>",
                $this->migrationsPath
            ));
        }
    }

    /**
     * Get Migrator instance
     *
     * @return Migrator
     */
    protected function getMigrator(): Migrator
    {
        $resolver = Procket::instance()->getDbManager()->getDatabaseManager();
        $repository = new DatabaseMigrationRepository($resolver, $this->repositoryTable);
        if (!$repository->repositoryExists()) {
            $repository->createRepository();
        }

        return new Migrator(
            $repository,
            $resolver,
            Procket::instance()->getFilesystem(),
            $resolver->getEventDispatcher()
        );
    }
}