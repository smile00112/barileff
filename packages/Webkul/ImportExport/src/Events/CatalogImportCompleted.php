<?php

namespace Webkul\ImportExport\Events;

use Webkul\ImportExport\Models\CatalogImportSession;

class CatalogImportCompleted
{
    public function __construct(public readonly CatalogImportSession $session) {}
}
