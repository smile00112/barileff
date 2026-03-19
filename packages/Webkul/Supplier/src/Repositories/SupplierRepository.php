<?php

namespace Webkul\Supplier\Repositories;

use Webkul\Core\Eloquent\Repository;

class SupplierRepository extends Repository
{
    public function model(): string
    {
        return 'Webkul\Supplier\Contracts\Supplier';
    }
}
