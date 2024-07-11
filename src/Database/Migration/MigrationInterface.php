<?php

namespace Pocket\Framework\Database\Migration;

/**
 * Migration interface
 */
interface MigrationInterface
{
    /**
     * run migration
     *
     * @return void
     */
    public function up();

    /**
     * rollback migration
     *
     * @return void
     */
    public function down();
}