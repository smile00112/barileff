<?php

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
        ->assertSeeText(trans('markup::app.admin.groups.create.title'));
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
        ->assertSeeText(trans('markup::app.admin.groups.edit.title'));
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
