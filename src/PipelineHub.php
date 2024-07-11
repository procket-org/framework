<?php

namespace Pocket\Framework;

use Illuminate\Pipeline\Hub as BaseHub;

class PipelineHub extends BaseHub
{
    /**
     * @inheritDoc
     */
    public function pipe($object, $pipeline = null)
    {
        $pipeline = $pipeline ?: 'default';

        return call_user_func(
            $this->pipelines[$pipeline], new Pipeline($this->container), $object
        );
    }
}