<?php

namespace Webkul\Menu\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Menu\Contracts\Menu as MenuContract;

class Menu extends Model implements MenuContract
{
    protected $table = 'site_menus';

    protected $fillable = [
        'code',
        'name',
        'location',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }
}
