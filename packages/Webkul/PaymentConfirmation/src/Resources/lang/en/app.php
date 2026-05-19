<?php

return [
    'admin' => [
        'payment-details' => [
            'create-title' => 'Add Payment Detail',
            'edit-title' => 'Edit Payment Detail',
            'index-title' => 'Payment Confirmation Details',
            'add-btn' => 'Add Payment Detail',
            'save-btn' => 'Save',
            'update-btn' => 'Update',
            'cancel-btn' => 'Cancel',
            'empty' => 'No payment details found. Add one above.',
            'delete-confirm' => 'Delete this payment detail?',
            'messages' => [
                'created' => 'Payment detail created successfully.',
                'updated' => 'Payment detail updated successfully.',
                'deleted' => 'Payment detail deleted.',
            ],
            'columns' => [
                'title' => 'Title',
                'inventory-source' => 'Inventory Source',
                'status' => 'Status',
                'actions' => 'Actions',
            ],
            'status' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
            ],
            'actions' => [
                'edit' => 'Edit',
                'delete' => 'Delete',
            ],
            'fields' => [
                'title' => 'Title',
                'instructions' => 'Instructions (shown to customer after placing order)',
                'inventory-source' => 'Inventory Source',
                'inventory-source-placeholder' => '— Select Source —',
                'active' => 'Active',
            ],
        ],
    ],

    'shop' => [
        'orders' => [
            'payment-confirmation' => [
                'title' => 'Payment Instructions',
                'attach-label' => 'Attach Payment Receipt',
                'submit-btn' => 'Submit Receipt',
                'awaiting' => 'Receipt submitted. Your payment is awaiting confirmation.',
                'confirmed' => 'Payment confirmed. Your order is being processed.',
            ],
        ],
    ],
];
