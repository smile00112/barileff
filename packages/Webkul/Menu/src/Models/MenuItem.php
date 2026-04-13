<?php

namespace Webkul\Menu\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\CMS\Models\Page;
use Webkul\Menu\Contracts\MenuItem as MenuItemContract;

class MenuItem extends Model implements MenuItemContract
{
    protected $table = 'site_menu_items';

    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'type',
        'cms_page_id',
        'url',
        'target',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function cmsPage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'cms_page_id');
    }
}
