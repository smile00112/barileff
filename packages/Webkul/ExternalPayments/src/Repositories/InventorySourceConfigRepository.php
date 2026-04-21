<?php

namespace Webkul\ExternalPayments\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\ExternalPayments\Models\InventorySourceConfig;

class InventorySourceConfigRepository extends Repository
{
    public function model(): string
    {
        return InventorySourceConfig::class;
    }
}
