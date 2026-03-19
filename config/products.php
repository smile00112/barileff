<?php

return [
    /**
     * Skip attribute during product copy.
     *
     * Supported Relations: ['categories', 'inventories', 'customer_group_prices', 'images', 'videos', 'product_relations']
     *
     * Support Attributes: All Attributes (Example: 'sku', 'product_number', etc)
     */
    'copy' => [
        'skip_attributes' => [],
    ],

    /**
     * Attributes exposed as top-level fields in product API responses.
     *
     * List attribute codes that should appear as separate fields
     * alongside id, sku, name in Shop and Admin API product resources.
     *
     * Example: ['brand', 'country_of_origin', 'color']
     */
    'api_exposed_attributes' => [],
];
