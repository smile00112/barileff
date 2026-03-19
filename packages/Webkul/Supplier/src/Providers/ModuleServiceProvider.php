<?php

namespace Webkul\Supplier\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Webkul\Supplier\Models\Supplier::class,
    ];
}
