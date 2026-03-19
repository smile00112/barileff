<?php

namespace Webkul\Markup\Repositories;

use Webkul\Core\Eloquent\Repository;

class MarkupGroupRepository extends Repository
{
    public function model(): string
    {
        return 'Webkul\Markup\Contracts\MarkupGroup';
    }
}
