<?php

return [
    'admin' => [
        'common' => [
            'create' => 'Create',
            'save' => 'Save',
            'back' => 'Back',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'active' => 'Active',
            'inactive' => 'Inactive',
        ],

        'acl' => [
            'menu' => 'Menu',
            'menus' => 'Menus',
            'items' => 'Items',
            'create' => 'Create',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'sort' => 'Sort',
        ],

        'menus' => [
            'title' => 'Menus',
            'create' => 'Create Menu',
            'edit' => 'Edit Menu',
            'fields' => [
                'name' => 'Name',
                'code' => 'Code',
                'location' => 'Location',
                'status' => 'Status',
            ],
            'actions' => [
                'items' => 'Items',
            ],
            'messages' => [
                'create-success' => 'Menu created successfully.',
                'update-success' => 'Menu updated successfully.',
                'delete-success' => 'Menu deleted successfully.',
            ],
        ],

        'items' => [
            'title' => 'Menu Items',
            'messages' => [
                'create-success' => 'Menu item created successfully.',
                'update-success' => 'Menu item updated successfully.',
                'delete-success' => 'Menu item deleted successfully.',
                'sort-success' => 'Menu items sorted successfully.',
            ],
        ],
    ],
];
