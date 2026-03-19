<?php

namespace Webkul\Markup\Repositories;

use Webkul\Core\Eloquent\Repository;

class MarkupConditionRepository extends Repository
{
    public function model(): string
    {
        return 'Webkul\Markup\Contracts\MarkupCondition';
    }
}
