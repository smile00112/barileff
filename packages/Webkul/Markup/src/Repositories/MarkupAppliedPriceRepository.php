<?php

namespace Webkul\Markup\Repositories;

use Webkul\Core\Eloquent\Repository;

class MarkupAppliedPriceRepository extends Repository
{
    public function model(): string
    {
        return 'Webkul\Markup\Contracts\MarkupAppliedPrice';
    }
}
