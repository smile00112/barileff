<?php

namespace Webkul\Admin\Events;

use Webkul\Admin\Models\CatalogImportSession;

class CatalogImportCompleted
{
    public function __construct(public readonly CatalogImportSession $session) {}
}
