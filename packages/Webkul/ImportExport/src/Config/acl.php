<?php

return [
    [
        'key' => 'catalog.imports',
        'name' => 'admin::app.acl.catalog-imports',
        'route' => 'admin.catalog.imports.index',
        'sort' => 5,
    ], [
        'key' => 'settings.data_transfer',
        'name' => 'admin::app.acl.data-transfer',
        'route' => 'admin.settings.data_transfer.imports.index',
        'sort' => 12,
    ], [
        'key' => 'settings.data_transfer.imports',
        'name' => 'admin::app.acl.imports',
        'route' => 'admin.settings.data_transfer.imports.index',
        'sort' => 1,
    ], [
        'key' => 'settings.data_transfer.imports.create',
        'name' => 'admin::app.acl.create',
        'route' => 'admin.settings.data_transfer.imports.create',
        'sort' => 1,
    ], [
        'key' => 'settings.data_transfer.imports.edit',
        'name' => 'admin::app.acl.edit',
        'route' => 'admin.settings.data_transfer.imports.edit',
        'sort' => 2,
    ], [
        'key' => 'settings.data_transfer.imports.delete',
        'name' => 'admin::app.acl.delete',
        'route' => 'admin.settings.data_transfer.imports.delete',
        'sort' => 3,
    ], [
        'key' => 'settings.data_transfer.imports.import',
        'name' => 'admin::app.acl.import',
        'route' => 'admin.settings.data_transfer.imports.import',
        'sort' => 4,
    ],
];
