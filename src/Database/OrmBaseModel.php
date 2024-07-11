<?php

namespace Pocket\Framework\Database;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Pocket\Framework\Pocket;

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
        Pocket::instance()->getDbManager();
        parent::booting();
    }
}