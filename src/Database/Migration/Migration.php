<?php

namespace Pocket\Framework\Database\Migration;

use Illuminate\Database\Migrations\Migration as BaseMigration;
use Illuminate\Database\Schema\Builder as DbSchemaBuilder;
use Pocket\Framework\Pocket;

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
        $connection = $this->connection ?: Pocket::instance()->defaultDbConnection;

        return Pocket::instance()->getDbSchema($connection);
    }
}