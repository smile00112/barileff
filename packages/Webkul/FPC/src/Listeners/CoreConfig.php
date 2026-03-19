<?php

namespace Webkul\FPC\Listeners;

use Webkul\FPC\Concerns\ClearsApiCache;

class CoreConfig
{
    use ClearsApiCache;

    /**
     * After core configuration update.
     *
     * @return void
     */
    public function afterUpdate()
    {
        $this->clearApiCacheAndWarm();
    }
}
