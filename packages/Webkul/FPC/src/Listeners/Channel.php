<?php

namespace Webkul\FPC\Listeners;

use Webkul\FPC\Concerns\ClearsApiCache;

class Channel
{
    use ClearsApiCache;

    /**
     * After channel update.
     *
     * @param  \Webkul\Core\Contracts\Channel  $channel
     * @return void
     */
    public function afterUpdate($channel)
    {
        $this->clearApiCacheAndWarm();
    }
}
