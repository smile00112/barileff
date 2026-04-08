<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Webkul\Inventory\Models\InventorySource;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;
use Webkul\User\Support\StoreManagerRolePermissions;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('should returns the user index page', function () {
    // Act and Assert.
    $this->loginAsAdmin();

    get(route('admin.settings.users.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.settings.users.index.title'))
        ->assertSeeText(trans('admin::app.settings.users.index.create.title'));
});

it('should fail the validation with errors when certain field not provided when store the users', function () {
    // Act and Assert.
    $this->loginAsAdmin();

    postJson(route('admin.settings.users.store'))
        ->assertJsonValidationErrorFor('name')
        ->assertJsonValidationErrorFor('email')
        ->assertJsonValidationErrorFor('role_id')
        ->assertUnprocessable();
});

it('should fail the validation with errors when confirm password not provided when store the users', function () {
    // Act and Assert.
    $this->loginAsAdmin();

    postJson(route('admin.settings.users.store'), [
        'password' => 'admin123',
    ])
        ->assertJsonValidationErrorFor('name')
        ->assertJsonValidationErrorFor('email')
        ->assertJsonValidationErrorFor('role_id')
        ->assertJsonValidationErrorFor('password_confirmation')
        ->assertUnprocessable();
});

it('should store the newly created admin', function () {
    // Act and Assert.
    $this->loginAsAdmin();

    postJson(route('admin.settings.users.store'), $data = [
        'name' => fake()->name(),
        'role_id' => 1,
        'email' => fake()->email,
        'password' => $password = fake()->password(),
        'password_confirmation' => $password,
        'image' => [
            UploadedFile::fake()->image('avatar.jpg'),
        ],
    ])
        ->assertOk()
        ->assertJsonPath('message', trans('admin::app.settings.users.create-success'));

    $this->assertModelWise([
        Admin::class => [
            [
                'name' => $data['name'],
                'email' => $data['email'],
                'role_id' => 1,
            ],
        ],
    ]);
});

it('should fails the validation error with tempered avatar provided when store the admin', function () {
    // Act and Assert.
    $this->loginAsAdmin();

    postJson(route('admin.settings.users.store'), [
        'name' => fake()->name(),
        'role_id' => 1,
        'email' => fake()->email(),
        'password' => $password = fake()->password(),
        'password_confirmation' => $password,
        'image' => [
            UploadedFile::fake()->image('avatar.php'),
        ],
    ])
        ->assertJsonValidationErrorFor('image.0')
        ->assertUnprocessable();
});

it('should returns the user and its roles', function () {
    // Arrange.
    $admin = Admin::factory()->create();

    // Act and Assert.
    $this->loginAsAdmin();

    $response = get(route('admin.settings.users.edit', $admin->id))
        ->assertOk();

    $roles = collect($response->json('roles'));
    $administrator = $roles->firstWhere('id', 1);

    expect($administrator)->not->toBeNull()
        ->and($administrator['name'])->toBe(trans('installer::app.seeders.user.roles.name'));

    $response->assertJsonPath('user.id', $admin->id)
        ->assertJsonPath('user.email', $admin->email);
});

it('computes store manager permission keys excluding configuration and users or roles settings', function () {
    $keys = StoreManagerRolePermissions::keys();

    expect($keys)->not->toBeEmpty()
        ->not->toContain('configuration')
        ->not->toContain('settings.users')
        ->not->toContain('settings.users.create')
        ->not->toContain('settings.roles')
        ->not->toContain('settings.roles.edit');
});

it('store manager role has custom permissions when present in database', function () {
    $role = Role::query()->find(2);

    if ($role === null) {
        $this->markTestSkipped('Store Manager role (id=2) is not present; run migrations.');
    }

    expect($role->permission_type)->toBe('custom');

    $permissions = $role->permissions;
    expect($permissions)->toBeArray()->not->toBeEmpty()
        ->and($permissions)->not->toContain('configuration')
        ->not->toContain('settings.users')
        ->not->toContain('settings.roles');
});

it('should fail the validation with errors when certain field not provided when update the users', function () {
    // Arrange.
    $admin = Admin::factory()->create();

    // Act and Assert.
    $this->loginAsAdmin();

    putJson(route('admin.settings.users.update'), [
        'id' => $admin->id,
        'password' => 'admin123',
    ])
        ->assertJsonValidationErrorFor('name')
        ->assertJsonValidationErrorFor('email')
        ->assertJsonValidationErrorFor('role_id')
        ->assertJsonValidationErrorFor('password_confirmation')
        ->assertUnprocessable();
});

it('should update the existing admin', function () {
    // Arrange.
    $admin = Admin::factory()->create();

    // Act and Assert.
    $this->loginAsAdmin();

    putJson(route('admin.settings.users.update'), $data = [
        'id' => $admin->id,
        'name' => $admin->name,
        'image' => [
            UploadedFile::fake()->image('avatar.jpg'),
        ],
        'role_id' => 1,
        'email' => fake()->email(),
        'password' => $password = fake()->password(),
        'password_confirmation' => $password,
    ])
        ->assertOk()
        ->assertJsonPath('message', trans('admin::app.settings.users.update-success'));

    $this->assertModelWise([
        Admin::class => [
            Arr::except($data, ['image', 'password_confirmation', 'password']),
        ],
    ]);
});

it('should fails the validation error with tempered avatar provided when update the admin', function () {
    // Arrange.
    $admin = Admin::factory()->create();

    // Act and Assert.
    $this->loginAsAdmin();

    putJson(route('admin.settings.users.update'), [
        'id' => $admin->id,
        'name' => $admin->name,
        'image' => [
            UploadedFile::fake()->image('avatar.php'),
        ],
        'role_id' => 1,
        'email' => fake()->email(),
        'password' => $password = fake()->password(),
        'password_confirmation' => $password,
    ])
        ->assertJsonValidationErrorFor('image.0')
        ->assertUnprocessable();
});

it('can update the users without new image', function () {
    // Arrange.
    $admin = Admin::factory()->create();
    $admin->update([
        'image' => 'avatar.jpg',
    ]);
    $admin->refresh();

    // Act and Assert.
    $this->loginAsAdmin($admin);

    putJson(route('admin.settings.users.update'), [
        'id' => $admin->id,
        'name' => $admin->name,
        'image' => [
            'image' => '',
        ],
        'role_id' => 1,
        'email' => fake()->email(),
        'password' => $password = fake()->password(),
        'password_confirmation' => $password,
    ])
        ->assertOk();

    $this->assertDatabaseHas('admins', [
        'id' => $admin->id,
        'image' => $admin->image,
    ]);
});

it('should delete the existing admin', function () {
    // Arrange.
    $admin = Admin::factory()->create();

    // Act and Assert.
    $this->loginAsAdmin();

    deleteJson(route('admin.settings.users.delete', $admin->id))
        ->assertOk()
        ->assertJsonPath('message', trans('admin::app.settings.users.delete-success'));

    $this->assertDatabaseMissing('admins', [
        'id' => $admin->id,
    ]);
});

it('should sync inventory sources when storing a new admin', function () {
    // Arrange.
    $source = InventorySource::factory()->create();

    // Act and Assert.
    $this->loginAsAdmin();

    postJson(route('admin.settings.users.store'), [
        'name' => fake()->name(),
        'email' => fake()->email(),
        'role_id' => 1,
        'password' => $password = 'admin1234',
        'password_confirmation' => $password,
        'inventory_source_ids' => [$source->id],
    ])->assertOk();

    $createdAdmin = Admin::latest('id')->first();

    $this->assertDatabaseHas('admin_inventory_sources', [
        'admin_id' => $createdAdmin->id,
        'inventory_source_id' => $source->id,
    ]);
});

it('should sync inventory sources when updating an admin', function () {
    // Arrange.
    $admin = Admin::factory()->create();
    $source = InventorySource::factory()->create();

    // Act and Assert.
    $this->loginAsAdmin();

    putJson(route('admin.settings.users.update'), [
        'id' => $admin->id,
        'name' => $admin->name,
        'email' => fake()->email(),
        'role_id' => 1,
        'password' => $password = 'admin1234',
        'password_confirmation' => $password,
        'inventory_source_ids' => [$source->id],
    ])->assertOk();

    $this->assertDatabaseHas('admin_inventory_sources', [
        'admin_id' => $admin->id,
        'inventory_source_id' => $source->id,
    ]);
});

it('should return assigned inventory source ids when editing an admin', function () {
    // Arrange.
    $source = InventorySource::factory()->create();
    $admin = Admin::factory()->create();
    $admin->inventorySources()->attach($source->id);

    // Act and Assert.
    $this->loginAsAdmin();

    get(route('admin.settings.users.edit', $admin->id))
        ->assertOk()
        ->assertJsonPath('userInventorySourceIds.0', $source->id);
});

it('should delete self admin', function () {
    // Arrange.
    $admin = Admin::factory()->create([
        'password' => Hash::make($password = fake()->password()),
    ]);

    // Act and Assert.
    $this->loginAsAdmin($admin);

    putJson(route('admin.settings.users.destroy'), [
        'password' => $password,
    ])
        ->assertOk()
        ->assertJsonPath('redirectUrl', route('admin.session.create'))
        ->assertJsonPath('message', trans('admin::app.settings.users.delete-success'));
});
