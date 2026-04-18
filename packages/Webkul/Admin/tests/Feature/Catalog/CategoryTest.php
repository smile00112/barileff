<?php

use Illuminate\Http\UploadedFile;
use Webkul\Attribute\Models\Attribute;
use Webkul\Category\Models\Category;
use Webkul\Category\Models\CategoryTranslation;
use Webkul\Faker\Helpers\Category as CategoryFaker;
use Webkul\User\Models\Admin as AdminModel;
use Webkul\User\Models\Role;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('should show category page', function () {
    // Act and Assert.
    $this->loginAsAdmin();

    get(route('admin.catalog.categories.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.categories.index.title'));
});

it('includes category logo url in category tree when logo is set', function () {
    $this->loginAsAdmin();

    $category = (new CategoryFaker)->factory()->create();

    $category->forceFill([
        'logo_path' => 'category/'.$category->id.'/logo.jpg',
    ])->save();

    $logoPath = 'category/'.$category->id.'/logo.jpg';

    expect($category->fresh()->logo_url)->not->toBeEmpty();

    $response = get(route('admin.catalog.categories.index'));

    $response->assertOk();

    $jsonLogoPath = str_replace('/', '\/', $logoPath);

    expect($response->getContent())->toContain('"logo_path":"'.$jsonLogoPath.'"');
});

it('should return categories index datagrid as json for ajax pagination', function () {
    $this->loginAsAdmin();

    getJson(
        route('admin.catalog.categories.index').'?'.http_build_query([
            'pagination' => [
                'page' => 1,
                'per_page' => 10,
            ],
        ]),
        ['X-Requested-With' => 'XMLHttpRequest']
    )->assertOk();
});

it('should show category edit page', function () {
    // Arrange.
    $category = (new CategoryFaker)->factory()->create();

    // Act and Assert.
    $this->loginAsAdmin();

    get(route('admin.catalog.categories.edit', $category->id))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.categories.edit.title'));
});

it('should return listing items of categories', function () {
    // Arrange.
    $category = (new CategoryFaker)->factory()->create();

    // Act and Assert.
    $this->loginAsAdmin();

    getJson(route('admin.catalog.categories.index'), [
        'X-Requested-With' => 'XMLHttpRequest',
    ])
        ->assertOk()
        ->assertJsonPath('records.0.category_id', $category->id)
        ->assertJsonPath('meta.total', 2);
});

it('should fail the validation with errors of logo path is not an array and image', function () {
    // Act and Assert.
    $this->loginAsAdmin();

    postJson(route('admin.catalog.categories.store'), [
        'logo_path' => fake()->word(),
        'banner_path' => [UploadedFile::fake()->create('banner.jpg')],
    ])
        ->assertJsonValidationErrorFor('logo_path')
        ->assertJsonValidationErrorFor('name')
        ->assertJsonValidationErrorFor('position')
        ->assertJsonValidationErrorFor('slug')
        ->assertUnprocessable();
});

it('should fails the image validation error when provided tempered logo and banner', function () {
    // Arrange.
    $attributes = Attribute::where('is_filterable', 1)->pluck('id')->toArray();

    // Act and Assert.
    $this->loginAsAdmin();

    postJson(route('admin.catalog.categories.store'), [
        'slug' => fake()->slug(),
        'name' => fake()->name(),
        'position' => rand(1, 5),
        'description' => substr(fake()->paragraph(), 0, 50),
        'attributes' => $attributes,
        'logo_path' => [
            UploadedFile::fake()->image('logo.php'),
        ],
        'banner_path' => [
            UploadedFile::fake()->image('banner.js'),
        ],
    ])
        ->assertJsonValidationErrorFor('logo_path.0')
        ->assertJsonValidationErrorFor('banner_path.0')
        ->assertUnprocessable();
});

it('should create a category', function () {
    // Arrange.
    $attributes = Attribute::where('is_filterable', 1)->pluck('id')->toArray();

    // Act and Assert.
    $this->loginAsAdmin();

    postJson(route('admin.catalog.categories.store'), $data = [
        'slug' => fake()->slug(),
        'name' => fake()->name(),
        'position' => rand(1, 5),
        'description' => substr(fake()->paragraph(), 0, 50),
        'attributes' => $attributes,
        'logo_path' => [
            UploadedFile::fake()->image('logo.png'),
        ],
        'banner_path' => [
            UploadedFile::fake()->image('banner.png'),
        ],
    ])
        ->assertRedirect(route('admin.catalog.categories.index'))
        ->isRedirection();

    $this->assertModelWise([
        CategoryTranslation::class => [
            [
                'slug' => $data['slug'],
                'name' => $data['name'],
                'description' => $data['description'],
            ],
        ],
    ]);
});

it('should fail the validation with errors when certain inputs are not provided when store in category', function () {
    // Act and Assert.
    $this->loginAsAdmin();

    postJson(route('admin.catalog.categories.store'))
        ->assertJsonValidationErrorFor('attributes')
        ->assertJsonValidationErrorFor('name')
        ->assertJsonValidationErrorFor('position')
        ->assertJsonValidationErrorFor('slug')
        ->assertUnprocessable();
});

it('should fail the validation with errors of description if display mode products_and_description when store', function () {
    // Act and Assert.
    $this->loginAsAdmin();

    postJson(route('admin.catalog.categories.store'), [
        'display_mode' => 'products_and_description',
    ])
        ->assertJsonValidationErrorFor('attributes')
        ->assertJsonValidationErrorFor('description')
        ->assertJsonValidationErrorFor('name')
        ->assertJsonValidationErrorFor('position')
        ->assertJsonValidationErrorFor('slug')
        ->assertUnprocessable();
});

it('should fail the validation with errors slug is already taken', function () {
    // Act and Assert.
    $this->loginAsAdmin();

    postJson(route('admin.catalog.categories.store'), [
        'slug' => 'root',
    ])
        ->assertJsonValidationErrorFor('attributes')
        ->assertJsonValidationErrorFor('name')
        ->assertJsonValidationErrorFor('position')
        ->assertJsonValidationErrorFor('slug')
        ->assertUnprocessable();
});

it('should fail the validation with errors when certain inputs are not provided when update in category', function () {
    // Arrange.
    $category = (new CategoryFaker)->factory()->create();

    $localeCode = core()->getRequestedLocaleCode();

    // Act and Assert.
    $this->loginAsAdmin();

    putJson(route('admin.catalog.categories.update', $category->id))
        ->assertJsonValidationErrorFor($localeCode.'.name')
        ->assertJsonValidationErrorFor($localeCode.'.slug')
        ->assertJsonValidationErrorFor('position')
        ->assertJsonValidationErrorFor('attributes')
        ->assertUnprocessable();
});

it('should fail the validation with errors when certain inputs are not provided and display mode products and description when update in category', function () {
    // Arrange.
    $category = (new CategoryFaker)->factory()->create();

    $localeCode = core()->getRequestedLocaleCode();

    // Act and Assert.
    $this->loginAsAdmin();

    putJson(route('admin.catalog.categories.update', $category->id), [
        'display_mode' => 'products_and_description',
    ])
        ->assertJsonValidationErrorFor($localeCode.'.name')
        ->assertJsonValidationErrorFor($localeCode.'.slug')
        ->assertJsonValidationErrorFor($localeCode.'.description')
        ->assertJsonValidationErrorFor('position')
        ->assertJsonValidationErrorFor('attributes')
        ->assertUnprocessable();
});

it('should fails the validation with certain provided inputs', function () {
    // Arrange.
    $category = (new CategoryFaker)->factory()->create();

    $attributes = Attribute::where('is_filterable', 1)->pluck('id')->toArray();

    // Act and Assert.
    $this->loginAsAdmin();

    putJson(route('admin.catalog.categories.update', $category->id), [
        'en' => [
            'name' => $name = fake()->name(),
            'slug' => $category->slug,
            'description' => $description = substr(fake()->paragraph(), 0, 50),
        ],
        'locale' => config('app.locale'),
        'attributes' => $attributes,
        'position' => rand(1, 5),
        'logo_path' => [
            UploadedFile::fake()->image('logo.py'),
        ],
        'banner_path' => [
            UploadedFile::fake()->image('banner.js'),
        ],
    ])
        ->assertJsonValidationErrorFor('logo_path.0')
        ->assertJsonValidationErrorFor('banner_path.0')
        ->assertUnprocessable();
});

it('should update a category', function () {
    // Arrange.
    $category = (new CategoryFaker)->factory()->create();

    $attributes = Attribute::where('is_filterable', 1)->pluck('id')->toArray();

    // Act and Assert.
    $this->loginAsAdmin();

    putJson(route('admin.catalog.categories.update', $category->id), [
        'en' => $data = [
            'name' => fake()->name(),
            'description' => substr(fake()->paragraph(), 0, 50),
            'slug' => $category->slug,
        ],
        'locale' => config('app.locale'),
        'attributes' => $attributes,
        'position' => rand(1, 5),
        'logo_path' => [
            UploadedFile::fake()->image('logo.png'),
        ],
        'banner_path' => [
            UploadedFile::fake()->image('banner.png'),
        ],
    ])
        ->assertRedirect(route('admin.catalog.categories.index'))
        ->isRedirection();

    $this->assertModelWise([
        CategoryTranslation::class => [
            [
                'name' => $data['name'],
                'slug' => $category->slug,
                'description' => $data['description'],
            ],
        ],
    ]);
});

it('should delete a category', function () {
    // Arrange.
    $category = (new CategoryFaker)->factory()->create();

    // Act and Assert.
    $this->loginAsAdmin();

    deleteJson(route('admin.catalog.categories.delete', $category->id))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.categories.delete-success'));

    $this->assertDatabaseMissing('categories', [
        'id' => $category->id,
    ]);
});

it('should delete mass categories', function () {
    // Arrange.
    $categories = (new CategoryFaker)->create(5);

    // Act and Assert.
    $this->loginAsAdmin();

    postJson(route('admin.catalog.categories.mass_delete', [
        'indices' => $categories->pluck('id')->toArray(),
    ]))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.categories.delete-success'));

    foreach ($categories as $category) {
        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }
});

it('should update mass categories', function () {
    // Arrange.
    $categories = (new CategoryFaker)->create(5);

    // Act and Assert.
    $this->loginAsAdmin();

    postJson(route('admin.catalog.categories.mass_update', [
        'indices' => $categories->pluck('id')->toArray(),
        'value' => 1,
    ]))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.categories.update-success'));

    foreach ($categories as $category) {
        $this->assertModelWise([
            Category::class => [
                [
                    'id' => $category->id,
                    'status' => 1,
                ],
            ],
        ]);
    }
});

it('should search categories with mega search', function () {
    // Arrange.
    $category = (new CategoryFaker)->factory()->create();

    // Act and Assert.
    $this->loginAsAdmin();

    getJson(route('admin.catalog.categories.search', [
        'query' => $category->name,
    ]))
        ->assertOk()
        ->assertJsonPath('data.0.id', $category->id)
        ->assertJsonPath('total', 1);
});

it('should show the tree view of categories', function () {
    // Arrange.
    $category = (new CategoryFaker)->factory()->create();

    // Act and Assert.
    $this->loginAsAdmin();

    getJson(route('admin.catalog.categories.tree'))
        ->assertOk()
        ->assertJsonPath('data.0.id', $category->id);
});

it('should reorder categories and persist new positions', function () {
    // Arrange.
    $categories = (new CategoryFaker)->create(3);

    $positions = $categories->map(fn ($category, $index) => [
        'id' => $category->id,
        'position' => $index + 1,
    ])->values()->toArray();

    // Act and Assert.
    $this->loginAsAdmin();

    postJson(route('admin.catalog.categories.reorder'), ['positions' => $positions])
        ->assertOk()
        ->assertJson(['message' => trans('admin::app.catalog.categories.reorder-success')]);

    foreach ($positions as $item) {
        $this->assertDatabaseHas('categories', [
            'id' => $item['id'],
            'position' => $item['position'],
        ]);
    }
});

it('should pass product counts to category index view', function () {
    // Arrange.
    $category = (new CategoryFaker)->factory()->create();

    // Act and Assert.
    $this->loginAsAdmin();

    get(route('admin.catalog.categories.index'))
        ->assertOk()
        ->assertViewHas('productCounts');
});

it('should return 403 when user lacks catalog.categories.edit permission on reorder', function () {
    // Arrange.
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions' => ['catalog.categories'],
    ]);

    $admin = AdminModel::factory()->create(['role_id' => $role->id]);

    $this->loginAsAdmin($admin);

    $categories = (new CategoryFaker)->create(2);

    $positions = $categories->map(fn ($category, $index) => [
        'id' => $category->id,
        'position' => $index + 1,
    ])->values()->toArray();

    // Act and Assert.
    postJson(route('admin.catalog.categories.reorder'), ['positions' => $positions])
        ->assertForbidden();
});
