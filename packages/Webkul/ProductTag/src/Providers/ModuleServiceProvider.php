<?php

namespace Webkul\ProductTag\Providers;

use Konekt\Concord\BaseModuleServiceProvider;
use Webkul\ProductTag\Models\Tag;

class ModuleServiceProvider extends BaseModuleServiceProvider
{
    protected $models = [
        Tag::class,
    ];
}
