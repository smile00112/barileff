<?php

return [
    [
        'key' => 'menu',
        'name' => 'menu::app.admin.acl.menu',
        'route' => 'admin.menu.menus.index',
        'sort' => 5,
    ], [
        'key' => 'menu.menus',
        'name' => 'menu::app.admin.acl.menus',
        'route' => 'admin.menu.menus.index',
        'sort' => 1,
    ], [
        'key' => 'menu.menus.create',
        'name' => 'menu::app.admin.acl.create',
        'route' => 'admin.menu.menus.create',
        'sort' => 1,
    ], [
        'key' => 'menu.menus.edit',
        'name' => 'menu::app.admin.acl.edit',
        'route' => 'admin.menu.menus.edit',
        'sort' => 2,
    ], [
        'key' => 'menu.menus.delete',
        'name' => 'menu::app.admin.acl.delete',
        'route' => 'admin.menu.menus.delete',
        'sort' => 3,
    ], [
        'key' => 'menu.items',
        'name' => 'menu::app.admin.acl.items',
        'route' => 'admin.menu.items.index',
        'sort' => 2,
    ], [
        'key' => 'menu.items.create',
        'name' => 'menu::app.admin.acl.create',
        'route' => 'admin.menu.items.store',
        'sort' => 1,
    ], [
        'key' => 'menu.items.edit',
        'name' => 'menu::app.admin.acl.edit',
        'route' => 'admin.menu.items.update',
        'sort' => 2,
    ], [
        'key' => 'menu.items.delete',
        'name' => 'menu::app.admin.acl.delete',
        'route' => 'admin.menu.items.delete',
        'sort' => 3,
    ], [
        'key' => 'menu.items.sort',
        'name' => 'menu::app.admin.acl.sort',
        'route' => 'admin.menu.items.sort',
        'sort' => 4,
    ],
];
