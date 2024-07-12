<?php

namespace Procket\Framework\Database;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Procket\Framework\Procket;

/**
 * ORM base model
 *
 * @mixin EloquentBuilder
 */
abstract class OrmBaseModel extends EloquentModel
{
    /**
     * @inheritDoc
     */
    protected static function booting(): void
    {
        Procket::instance()->getDbManager();
        parent::booting();
    }
}