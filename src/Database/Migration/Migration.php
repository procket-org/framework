<?php

namespace Procket\Framework\Database\Migration;

use Illuminate\Database\Migrations\Migration as BaseMigration;
use Illuminate\Database\Schema\Builder as DbSchemaBuilder;
use Procket\Framework\Procket;

/**
 * Migration base class
 */
abstract class Migration extends BaseMigration implements MigrationInterface
{
    /**
     * Get database schema builder
     *
     * @return DbSchemaBuilder
     */
    public function schema(): DbSchemaBuilder
    {
        $connection = $this->connection ?: Procket::instance()->defaultDbConnection;

        return Procket::instance()->getDbSchema($connection);
    }
}