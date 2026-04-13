<?php

use Webkul\Admin\Tests\AdminTestCase;
use Webkul\Menu\Models\Menu;

use function Pest\Laravel\get;

uses(AdminTestCase::class);

it('renders location field as select with unique existing locations on create page', function () {
    $this->loginAsAdmin();

    Menu::query()->create([
        'name' => 'Header Menu',
        'code' => 'header-menu',
        'location' => 'header',
        'is_active' => true,
    ]);

    Menu::query()->create([
        'name' => 'Footer Menu',
        'code' => 'footer-menu',
        'location' => 'footer',
        'is_active' => true,
    ]);

    Menu::query()->create([
        'name' => 'Duplicate Header Menu',
        'code' => 'header-menu-2',
        'location' => 'header',
        'is_active' => true,
    ]);

    $response = get(route('admin.menu.menus.create'))
        ->assertSuccessful()
        ->assertSee('name="location"', false)
        ->assertSee('<option value="header"', false)
        ->assertSee('<option value="footer"', false);

    expect(substr_count($response->getContent(), 'value="header"'))->toBe(1)
        ->and(substr_count($response->getContent(), 'value="footer"'))->toBe(1);
});

it('includes the edited menu location in the location select list', function () {
    $this->loginAsAdmin();

    Menu::query()->create([
        'name' => 'Header Menu',
        'code' => 'header-menu',
        'location' => 'header',
        'is_active' => true,
    ]);

    $menu = Menu::query()->create([
        'name' => 'Special Menu',
        'code' => 'special-menu',
        'location' => 'special-location',
        'is_active' => true,
    ]);

    get(route('admin.menu.menus.edit', $menu->id))
        ->assertSuccessful()
        ->assertSee('name="location"', false)
        ->assertSee('<option value="special-location" selected', false)
        ->assertSee('<option value="header"', false);
});
