<?php

return [
    [
        'key'   => 'catalog.markup',
        'name'  => 'markup::app.admin.acl.title',
        'route' => 'admin.markup.groups.index',
        'sort'  => 5,
    ],
    [
        'key'   => 'catalog.markup.create',
        'name'  => 'markup::app.admin.acl.create',
        'route' => 'admin.markup.groups.create',
        'sort'  => 1,
    ],
    [
        'key'   => 'catalog.markup.edit',
        'name'  => 'markup::app.admin.acl.edit',
        'route' => 'admin.markup.groups.edit',
        'sort'  => 2,
    ],
    [
        'key'   => 'catalog.markup.delete',
        'name'  => 'markup::app.admin.acl.delete',
        'route' => 'admin.markup.groups.destroy',
        'sort'  => 3,
    ],
];
