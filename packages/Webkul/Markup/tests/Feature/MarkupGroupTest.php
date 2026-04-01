<?php

use Webkul\Inventory\Models\InventorySource;
use Webkul\Markup\Models\MarkupGroup;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

it('should return the markup groups index page', function () {
    $this->loginAsAdmin();

    get(route('admin.markup.groups.index'))
        ->assertOk()
        ->assertSeeText(trans('markup::app.admin.groups.index.title'));
});

it('should return the markup groups create page', function () {
    $this->loginAsAdmin();

    get(route('admin.markup.groups.create'))
        ->assertOk()
        ->assertSeeText(trans('markup::app.admin.groups.create.title'))
        ->assertSeeText(trans('admin::app.components.layouts.header.back-btn'));
});

it('should store a new markup group', function () {
    $this->loginAsAdmin();

    post(route('admin.markup.groups.store'), [
        'name'                 => 'Test Markup Group',
        'type'                 => 'markup',
        'is_active'            => 1,
        'schedule_type'        => 'daily',
        'apply_to_all_sources' => 1,
        'sort_order'           => 0,
        'schedules'            => [
            ['time_from' => '09:00', 'time_to' => '18:00'],
        ],
        'conditions' => [
            [
                'adjustment_type'  => 'percent',
                'adjustment_value' => 10,
                'sort_order'       => 0,
            ],
        ],
    ])->assertRedirect(route('admin.markup.groups.index'));

    $this->assertDatabaseHas('markup_groups', [
        'name' => 'Test Markup Group',
        'type' => 'markup',
    ]);

    $this->assertDatabaseHas('markup_group_schedules', [
        'time_from' => '09:00:00',
        'time_to'   => '18:00:00',
    ]);

    $this->assertDatabaseHas('markup_conditions', [
        'adjustment_type'  => 'percent',
        'adjustment_value' => '10.0000',
    ]);
});

it('should store a discount group with cost range', function () {
    $this->loginAsAdmin();

    post(route('admin.markup.groups.store'), [
        'name'                 => 'Cost Range Discount',
        'type'                 => 'discount',
        'is_active'            => 1,
        'schedule_type'        => 'weekly',
        'apply_to_all_sources' => 1,
        'schedules'            => [
            ['day_of_week' => 1, 'time_from' => '08:00', 'time_to' => '20:00'],
        ],
        'conditions' => [
            [
                'cost_from'        => 100,
                'cost_to'          => 500,
                'adjustment_type'  => 'fixed',
                'adjustment_value' => 25,
                'sort_order'       => 0,
            ],
        ],
    ])->assertRedirect(route('admin.markup.groups.index'));

    $this->assertDatabaseHas('markup_groups', [
        'name'          => 'Cost Range Discount',
        'type'          => 'discount',
        'schedule_type' => 'weekly',
    ]);

    $this->assertDatabaseHas('markup_conditions', [
        'cost_from'        => '100.0000',
        'cost_to'          => '500.0000',
        'adjustment_type'  => 'fixed',
        'adjustment_value' => '25.0000',
    ]);
});

it('should return the edit page for a markup group', function () {
    $this->loginAsAdmin();

    $group = MarkupGroup::create([
        'name'                 => 'Edit Test',
        'type'                 => 'markup',
        'is_active'            => true,
        'schedule_type'        => 'daily',
        'apply_to_all_sources' => true,
        'sort_order'           => 0,
        'jobs_version'         => 0,
    ]);

    get(route('admin.markup.groups.edit', $group->id))
        ->assertOk()
        ->assertSeeText(trans('markup::app.admin.groups.edit.title'))
        ->assertSeeText(trans('admin::app.components.layouts.header.back-btn'));
});

it('should update an existing markup group', function () {
    $this->loginAsAdmin();

    $group = MarkupGroup::create([
        'name'                 => 'Before Update',
        'type'                 => 'markup',
        'is_active'            => true,
        'schedule_type'        => 'daily',
        'apply_to_all_sources' => true,
        'sort_order'           => 0,
        'jobs_version'         => 1,
    ]);

    put(route('admin.markup.groups.update', $group->id), [
        'name'                 => 'After Update',
        'type'                 => 'discount',
        'is_active'            => 1,
        'schedule_type'        => 'weekly',
        'apply_to_all_sources' => 1,
        'schedules'            => [
            ['day_of_week' => 5, 'time_from' => '10:00', 'time_to' => '16:00'],
        ],
        'conditions' => [
            [
                'adjustment_type'  => 'fixed',
                'adjustment_value' => 50,
                'sort_order'       => 0,
            ],
        ],
    ])->assertRedirect(route('admin.markup.groups.index'));

    $this->assertDatabaseHas('markup_groups', [
        'id'            => $group->id,
        'name'          => 'After Update',
        'type'          => 'discount',
        'schedule_type' => 'weekly',
    ]);
});

it('should delete a markup group', function () {
    $this->loginAsAdmin();

    $group = MarkupGroup::create([
        'name'                 => 'To Delete',
        'type'                 => 'markup',
        'is_active'            => true,
        'schedule_type'        => 'daily',
        'apply_to_all_sources' => true,
        'sort_order'           => 0,
        'jobs_version'         => 0,
    ]);

    deleteJson(route('admin.markup.groups.destroy', $group->id))
        ->assertOk();

    $this->assertDatabaseMissing('markup_groups', [
        'id' => $group->id,
    ]);
});

it('should fail validation when required fields are missing on store', function () {
    $this->loginAsAdmin();

    post(route('admin.markup.groups.store'), [])
        ->assertSessionHasErrors(['name', 'type', 'is_active', 'schedule_type', 'apply_to_all_sources', 'schedules', 'conditions']);
});

it('returns inventory sources as JSON for authenticated admin', function () {
    $this->loginAsAdmin();

    $source = InventorySource::factory()->create(['status' => 1]);

    get(route('admin.markup.groups.inventory-sources'))
        ->assertOk()
        ->assertJsonPath('data.0.id', $source->id)
        ->assertJsonPath('data.0.name', $source->name)
        ->assertJsonPath('data.0.code', $source->code);
});

it('redirects guest when requesting inventory sources JSON', function () {
    get(route('admin.markup.groups.inventory-sources'))
        ->assertRedirect();
});

it('validates inventory sources when apply to all sources is false', function () {
    $this->loginAsAdmin();

    post(route('admin.markup.groups.store'), [
        'name'                 => 'No Sources',
        'type'                 => 'markup',
        'is_active'            => 1,
        'schedule_type'        => 'daily',
        'apply_to_all_sources' => 0,
        'schedules'            => [
            ['time_from' => '09:00', 'time_to' => '18:00'],
        ],
        'conditions' => [
            [
                'adjustment_type'  => 'percent',
                'adjustment_value' => 10,
                'sort_order'       => 0,
            ],
        ],
    ])->assertSessionHasErrors(['inventory_sources']);
});

it('stores markup group with specific inventory sources', function () {
    $this->loginAsAdmin();

    $source = InventorySource::factory()->create(['status' => 1]);

    post(route('admin.markup.groups.store'), [
        'name'                 => 'Sources Test',
        'type'                 => 'markup',
        'is_active'            => 1,
        'schedule_type'        => 'daily',
        'apply_to_all_sources' => 0,
        'inventory_sources'    => [$source->id],
        'schedules'            => [
            ['time_from' => '09:00', 'time_to' => '18:00'],
        ],
        'conditions' => [
            [
                'adjustment_type'  => 'percent',
                'adjustment_value' => 10,
                'sort_order'       => 0,
            ],
        ],
    ])->assertRedirect(route('admin.markup.groups.index'));

    $group = MarkupGroup::query()->where('name', 'Sources Test')->first();

    expect($group)->not->toBeNull();

    $this->assertDatabaseHas('markup_group_inventory_sources', [
        'markup_group_id'       => $group->id,
        'inventory_source_id'   => $source->id,
    ]);
});

it('resolves markup menu and admin header back translation keys', function () {
    app()->setLocale('en');

    expect(trans('markup::app.admin.menu.title'))->toBe('Markup')
        ->and(trans('markup::app.admin.acl.title'))->toBe('Markup')
        ->and(trans('admin::app.components.layouts.header.back-btn'))->toBe('Back');
});
