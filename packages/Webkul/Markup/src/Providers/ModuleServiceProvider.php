<?php

namespace Webkul\Markup\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        \Webkul\Markup\Models\MarkupGroup::class,
        \Webkul\Markup\Models\MarkupGroupSchedule::class,
        \Webkul\Markup\Models\MarkupCondition::class,
        \Webkul\Markup\Models\MarkupAppliedPrice::class,
        \Webkul\Markup\Models\MarkupLog::class,
    ];
}
