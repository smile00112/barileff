<?php

return [
    'admin' => [
        'groups' => [
            'created' => 'Markup group created successfully.',
            'updated' => 'Markup group updated successfully.',
            'deleted' => 'Markup group deleted successfully.',

            'index' => [
                'title'      => 'Markup Groups',
                'create-btn' => 'Create Group',
            ],

            'create' => [
                'title'    => 'Create Markup Group',
                'save-btn' => 'Save',
            ],

            'edit' => [
                'title'    => 'Edit Markup Group',
                'save-btn' => 'Save',
            ],

            'form' => [
                'general'              => 'General Information',
                'name'                 => 'Name',
                'description'          => 'Description',
                'type'                 => 'Type',
                'type-markup'          => 'Markup',
                'type-discount'        => 'Discount',
                'schedule-type'        => 'Schedule Type',
                'daily'                => 'Daily',
                'weekly'               => 'Weekly',
                'status'               => 'Status',
                'active'               => 'Active',
                'inactive'             => 'Inactive',
                'sort-order'           => 'Sort Order',
                'apply-to-all-sources' => 'Apply to All Sources',
                'yes'                  => 'Yes',
                'no'                   => 'No',

                'schedules'   => 'Schedules',
                'day-of-week' => 'Day of Week',
                'every-day'   => 'Every Day',
                'time-from'   => 'Time From',
                'time-to'     => 'Time To',
                'add-schedule' => 'Add Schedule',
                'sunday'      => 'Sunday',
                'monday'      => 'Monday',
                'tuesday'     => 'Tuesday',
                'wednesday'   => 'Wednesday',
                'thursday'    => 'Thursday',
                'friday'      => 'Friday',
                'saturday'    => 'Saturday',

                'conditions'              => 'Conditions',
                'condition'               => 'Condition',
                'cost-from'               => 'Cost From',
                'cost-to'                 => 'Cost To',
                'adjustment-type'         => 'Adjustment Type',
                'adjustment-value'        => 'Adjustment Value',
                'percent'                 => 'Percent',
                'fixed'                   => 'Fixed',
                'categories'              => 'Categories (IDs)',
                'products'                => 'Products (IDs)',
                'categories-placeholder'  => 'e.g. 1, 2, 3',
                'products-placeholder'    => 'e.g. 10, 20, 30',
                'add-condition'           => 'Add Condition',

                'logs'         => 'Activity Log',
                'log-action'   => 'Action',
                'log-products' => 'Products',
                'log-message'  => 'Message',
                'log-date'     => 'Date',
            ],
        ],

        'datagrid' => [
            'id'             => 'ID',
            'name'           => 'Name',
            'type'           => 'Type',
            'schedule-type'  => 'Schedule',
            'status'         => 'Status',
            'applied'        => 'Applied',
            'active'         => 'Active',
            'inactive'       => 'Inactive',
            'yes'            => 'Yes',
            'no'             => 'No',
            'edit'           => 'Edit',
            'delete'         => 'Delete',

            'types' => [
                'markup'   => 'Markup',
                'discount' => 'Discount',
            ],

            'schedule-types' => [
                'daily'  => 'Daily',
                'weekly' => 'Weekly',
            ],
        ],
    ],
];
