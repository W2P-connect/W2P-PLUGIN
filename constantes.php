<?php


if (!defined("W2P_DISTANT_REST_URL")) {
    define("W2P_DISTANT_REST_URL", "https://w2p-website.vercel.app/api/v1");
}

if (!defined("W2P_VARIABLE_SOURCES")) {
    define("W2P_VARIABLE_SOURCES", [
        "user" => "user",
        "order" => "order",
        "product" => "product",
        "w2p" => "w2p"
    ]);
}

if (!defined("W2P_HOOK_SOURCES")) {
    define("W2P_HOOK_SOURCES", [
        "user" => "user",
        "order" => "order",
        "product" => "product",
    ]);
}

if (!defined("W2P_META_KEYS")) {
    define(
        'W2P_META_KEYS',
        [
            [
                'label' => "Woocomerce (order)",
                'description' => null,
                // 'toolTip' => "Please note, Woocommerce meta keys are generally only completed by the customer 
                // when the first order is placed. They are therefore often empty when the user registers.",
                'subcategories' => [
                    [
                        'label' => "order",
                        'metaKeys' => [
                            [
                                'label' => 'id',
                                'value' => 'id',
                                'source' => 'order',
                                'description' => "Order Id.",
                                'exemple' => '6452'
                            ],
                            [
                                'label' => 'Order total',
                                'value' => '_order_total',
                                'source' => 'order',
                                'description' => "Total amount of the order including taxes.",
                                'exemple' => '100.00'
                            ],
                            [
                                'label' => 'Order total (no tax)',
                                'recommanded' => true,
                                'source' => 'order',
                                'value' => '_order_total_excl_tax',
                                'description' => "Total amount of the order excluding taxes.",
                                'exemple' => '84.00'
                            ],
                            [
                                'label' => 'Order total tax',
                                'value' => '_order_tax',
                                'source' => 'order',
                                'description' => "Total tax amount for the order.",
                                'exemple' => '16.00'
                            ],
                            [
                                'label' => 'Order shipping amount',
                                'value' => '_order_shipping',
                                'source' => 'order',
                                'description' => "Total shipping cost for the order.",
                                'exemple' => '10.00'
                            ],
                            [
                                'label' => 'Order discount',
                                'value' => '_order_discount',
                                'source' => 'order',
                                'description' => "Total discount applied to the order.",
                                'exemple' => '5.00'
                            ],
                            [
                                'label' => 'Payment method',
                                'value' => '_payment_method',
                                'source' => 'order',
                                'description' => "Payment method used for the order.",
                                'exemple' => 'paypal'
                            ],
                            [
                                'label' => 'Order currency',
                                'value' => '_order_currency',
                                'source' => 'order',
                                'description' => "Currency used for the order.",
                                'exemple' => 'USD'
                            ],
                            [
                                'label' => 'Order status',
                                'value' => '_order_status',
                                'source' => 'order',
                                'recommanded' => true,
                                'description' => "Status of the order.",
                                'exemple' => 'completed'
                            ],
                            [
                                'label' => 'Shipping method',
                                'value' => '_shipping_method',
                                'source' => 'order',
                                'description' => "Shipping method used for the order.",
                                'exemple' => 'flat_rate'
                            ],
                            [
                                'label' => 'Customer note',
                                'value' => '_customer_note',
                                'source' => 'order',
                                'description' => "Note provided by the customer during checkout.",
                                'exemple' => 'Please leave the package at the front door.'
                            ],
                        ]
                    ],
                ],
                'allowedSource' => [W2P_HOOK_SOURCES["order"]]
            ],
            [
                'label' => "Woocomerce (product)",
                'description' => null,
                // 'toolTip' => "Please note, Woocommerce meta keys are generally only completed by the customer 
                // when the first order is placed. They are therefore often empty when the user registers.",
                'subcategories' => [
                    [
                        'label' => "product",
                        'metaKeys' => [
                            [
                                'label' => 'id',
                                'value' => 'id',
                                'source' => 'product',
                                'description' => 'Product ID.',
                                'exemple' => '123'
                            ],
                            [
                                'label' => 'name',
                                'value' => 'name',
                                'source' => 'product',
                                'recommanded' => true,
                                'description' => 'Product name.',
                                'exemple' => 'T-Shirt'
                            ],
                            [
                                'label' => 'attribute_summary',
                                'recommanded' => true,
                                'value' => 'attribute_summary',
                                'source' => 'product',
                                'description' => 'Summary of attributes for variations of variable products.',
                                'exemple' => 'Size: S, M, L - Color: Red, Blue, Green'
                            ],
                            [
                                'label' => 'slug',
                                'value' => 'slug',
                                'source' => 'product',
                                'description' => 'Product slug (permalink).',
                                'exemple' => 't-shirt'
                            ],
                            [
                                'label' => 'short_description',
                                'value' => 'short_description',
                                'source' => 'product',
                                'description' => 'Short description of the product.',
                                'exemple' => 'Stylish t-shirt.'
                            ],
                            [
                                'label' => 'sku',
                                'value' => 'sku',
                                'source' => 'product',
                                'description' => 'Product SKU.',
                                'exemple' => 'TSHIRT-001'
                            ],
                            [
                                'label' => 'price',
                                'value' => 'price',
                                'source' => 'product',
                                'description' => 'Current price of the product.',
                                'exemple' => '25.00'
                            ],
                            [
                                'label' => 'regular_price',
                                'value' => 'regular_price',
                                'source' => 'product',
                                'description' => 'Regular price of the product.',
                                'exemple' => '30.00'
                            ],
                            [
                                'label' => 'sale_price',
                                'value' => 'sale_price',
                                'source' => 'product',
                                'description' => 'Sale price of the product.',
                                'exemple' => '25.00'
                            ],
                            [
                                'label' => 'tax_class',
                                'value' => 'tax_class',
                                'source' => 'product',
                                'description' => 'Tax class of the product.',
                                'exemple' => 'standard'
                            ],
                            [
                                'label' => 'stock_quantity',
                                'value' => 'stock_quantity',
                                'source' => 'product',
                                'description' => 'Quantity in stock.',
                                'exemple' => '100'
                            ],
                            [
                                'label' => 'weight',
                                'value' => 'weight',
                                'source' => 'product',
                                'description' => 'Weight of the product.',
                                'exemple' => '0.5'
                            ],
                            [
                                'label' => 'length',
                                'value' => 'length',
                                'source' => 'product',
                                'description' => 'Length of the product.',
                                'exemple' => '30'
                            ],
                            [
                                'label' => 'width',
                                'value' => 'width',
                                'source' => 'product',
                                'description' => 'Width of the product.',
                                'exemple' => '20'
                            ],
                            [
                                'label' => 'height',
                                'value' => 'height',
                                'source' => 'product',
                                'description' => 'Height of the product.',
                                'exemple' => '1'
                            ],
                            [
                                'label' => 'shipping_class',
                                'value' => 'shipping_class',
                                'source' => 'product',
                                'description' => 'Shipping class of the product.',
                                'exemple' => 'standard'
                            ],
                            // [
                            //     'label' => 'reviews_allowed',
                            //     'value' => 'reviews_allowed',
                            //     'description' => 'Indicates if reviews are allowed.',
                            //     'exemple' => 'true'
                            // ],
                            // [
                            //     'label' => 'average_rating',
                            //     'value' => 'average_rating',
                            //     'description' => 'Average rating of the product.',
                            //     'exemple' => '4.5'
                            // ],
                            // [
                            //     'label' => 'rating_count',
                            //     'value' => 'rating_count',
                            //     'description' => 'Number of ratings for the product.',
                            //     'exemple' => '50'
                            // ],
                            // [
                            //     'label' => 'related_ids',
                            //     'value' => 'related_ids',
                            //     'description' => 'IDs of related products.',
                            //     'exemple' => '[124, 125]'
                            // ],
                            // [
                            //     'label' => 'upsell_ids',
                            //     'value' => 'upsell_ids',
                            //     'description' => 'IDs of upsell products.',
                            //     'exemple' => '[126, 127]'
                            // ],
                            // [
                            //     'label' => 'cross_sell_ids',
                            //     'value' => 'cross_sell_ids',
                            //     'description' => 'IDs of cross-sell products.',
                            //     'exemple' => '[128, 129]'
                            // ],
                            // [
                            //     'label' => 'parent_id',
                            //     'value' => 'parent_id',
                            //     'description' => 'ID of the parent product (if applicable).',
                            //     'exemple' => '120'
                            // ],
                            [
                                'label' => 'categories',
                                'value' => 'categories',
                                'source' => 'product',
                                'description' => 'Categories of the product.',
                                'exemple' => '\'Clothing\', \'T-Shirts\''
                            ],
                            [
                                'label' => 'tags',
                                'value' => 'tags',
                                'source' => 'product',
                                'description' => 'Tags of the product.',
                                'exemple' => '\'Summer\', \'Cotton\''
                            ],
                            [
                                'label' => 'attributes',
                                'value' => 'attributes',
                                'source' => 'product',
                                'description' => 'Attributes of the product.',
                                'exemple' => "'Color': 'Blue', 'Size': 'Large'"
                            ],
                            [
                                'label' => 'default_attributes',
                                'value' => 'default_attributes',
                                'source' => 'product',
                                'description' => 'Default attributes for variable products.',
                                'exemple' => "'Color' => 'Red', 'Size' => 'Medium'"
                            ],
                            // [
                            //     'label' => 'menu_order',
                            //     'value' => 'menu_order',
                            //     'description' => 'Menu order for the product.',
                            //     'exemple' => '1'
                            // ],
                            // [
                            //     'label' => 'virtual',
                            //     'value' => 'virtual',
                            //     'description' => 'Indicates if the product is virtual.',
                            //     'exemple' => 'false'
                            // ],
                            // [
                            //     'label' => 'downloadable',
                            //     'value' => 'downloadable',
                            //     'description' => 'Indicates if the product is downloadable.',
                            //     'exemple' => 'false'
                            // ],
                            // [
                            //     'label' => 'downloads',
                            //     'value' => 'downloads',
                            //     'description' => 'Downloadable files for the product.',
                            //     'exemple' => '[\'file1.zip\', \'file2.pdf\']'
                            // ],                            
                        ]
                    ]
                ],
                'allowedSource' => [W2P_HOOK_SOURCES["product"]]
            ],
            [
                'label' => "Woocommerce (customer)",
                'description' => null,
                'toolTip' => "Please note, WooCommerce meta keys are generally only completed by the customer 
                when the first order is placed. They are therefore often empty when the user registers.",
                'subcategories' => [
                    [
                        'label' => "Identity",
                        'metaKeys' => [
                            [
                                'label' => 'Billing first_name',
                                'value' => 'billing_first_name',
                                'source' => 'user',
                                'description' => "Customer's billing first name.",
                                'exemple' => 'John'
                            ],
                            [
                                'label' => 'Billing last name',
                                'value' => 'billing_last_name',
                                'source' => 'user',
                                'description' => "Customer's billing last name.",
                                'exemple' => 'Doe'
                            ],
                            [
                                'label' => 'Billing company',
                                'value' => 'billing_company',
                                'source' => 'user',
                                'description' => "Customer's billing company name.",
                                'exemple' => 'ABC Corp'
                            ],
                            [
                                'label' => 'Shipping first name',
                                'value' => 'shipping_first_name',
                                'source' => 'user',
                                'description' => "Customer's shipping first name.",
                                'exemple' => 'John'
                            ],
                            [
                                'label' => 'Shipping last name',
                                'value' => 'shipping_last_name',
                                'source' => 'user',
                                'description' => "Customer's shipping last name.",
                                'exemple' => 'Doe'
                            ],
                            [
                                'label' => 'Shipping company',
                                'value' => 'shipping_company',
                                'source' => 'user ',
                                'description' => "Customer's shipping company name.",
                                'exemple' => 'XYZ Ltd'
                            ],
                        ],
                    ],
                    [
                        'label' => "Contact",
                        'metaKeys' => [
                            [
                                'label' => 'Billing email',
                                'value' => 'billing_email',
                                'source' => 'user',
                                'description' => "Customer's billing email address.",
                                'exemple' => 'john.doe@example.com'
                            ],
                            [
                                'label' => 'Billing phone',
                                'value' => 'billing_phone',
                                'source' => 'user',
                                'description' => "Customer's billing phone number.",
                                'exemple' => '123-456-7890'
                            ],
                        ],
                    ],

                    [
                        'label' => "Billing address",
                        'metaKeys' => [
                            [
                                'label' => 'Billing address 1',
                                'value' => 'billing_address_1',
                                'source' => 'user',
                                'description' => "First line of the customer's billing address.",
                                'exemple' => '123 Main Street'
                            ],
                            [
                                'label' => 'Billing address 2',
                                'value' => 'billing_address_2',
                                'source' => 'user',
                                'description' => "Second line of the customer's billing address.",
                                'exemple' => 'Apt 4B'
                            ],
                            [
                                'label' => 'Billing city',
                                'value' => 'billing_city',
                                'source' => 'user',
                                'description' => "Customer's billing city.",
                                'exemple' => 'Cityville'
                            ],
                            [
                                'label' => 'Billing postcode',
                                'value' => 'billing_postcode',
                                'source' => 'user',
                                'description' => "Customer's billing postal code.",
                                'exemple' => '12345'
                            ],
                            [
                                'label' => 'Billing country',
                                'value' => 'billing_country',
                                'source' => 'user',
                                'description' => "Customer's billing country.",
                                'exemple' => 'US'
                            ],
                            [
                                'label' => 'Billing state',
                                'value' => 'billing_state',
                                'source' => 'user',
                                'description' => "Customer's billing state or region.",
                                'exemple' => 'CA'
                            ],
                        ],
                    ],
                    [
                        'label' => "Shipping address",
                        'metaKeys' => [
                            [
                                'label' => 'Shipping address 1',
                                'value' => 'shipping_address_1',
                                'source' => 'user',
                                'description' => "First line of the customer's shipping address.",
                                'exemple' => '456 Shipping Lane'
                            ],
                            [
                                'label' => 'Shipping address 2',
                                'value' => 'shipping_address_2',
                                'source' => 'user',
                                'description' => "Second line of the customer's shipping address.",
                                'exemple' => 'Suite 8'
                            ],
                            [
                                'label' => 'Shipping city',
                                'value' => 'shipping_city',
                                'source' => 'user',
                                'description' => "Customer's shipping city.",
                                'exemple' => 'Shippingtown'
                            ],
                            [
                                'label' => 'Shipping postcode',
                                'value' => 'shipping_postcode',
                                'source' => 'user',
                                'description' => "Customer's shipping postal code.",
                                'exemple' => '54321'
                            ],
                            [
                                'label' => 'Shipping country',
                                'value' => 'shipping_country',
                                'source' => 'user',
                                'description' => "Customer's shipping country.",
                                'exemple' => 'CA'
                            ],
                            [
                                'label' => 'Shipping state',
                                'value' => 'shipping_state',
                                'source' => 'user',
                                'description' => "Customer's shipping state or region.",
                                'exemple' => 'ON'
                            ],
                        ],
                    ],
                ],
                'allowedSource' => [W2P_HOOK_SOURCES["user"], W2P_HOOK_SOURCES["order"]]
            ],
            [
                'label' => "Wordpress",
                'description' => null,
                'toolTip' => null,
                'subcategories' => [
                    [
                        'label' => "Identity",
                        'metaKeys' => [
                            [
                                'label' => 'First name',
                                'value' => 'first_name',
                                'source' => 'user',
                                'description' => 'User\'s first name.',
                                'exemple' => 'John'
                            ],
                            [
                                'label' => 'Last name',
                                'value' => 'last_name',
                                'source' => 'user',
                                'description' => 'User\'s last name.',
                                'exemple' => 'Doe'
                            ],
                            [
                                'label' => 'Nickname',
                                'value' => 'nickname',
                                'source' => 'user',
                                'description' => 'User\'s display name.',
                                'exemple' => 'john_doe'
                            ],
                            [
                                'label' => 'user email',
                                'value' => 'user_email',
                                'source' => 'user',
                                'description' => 'User\'s email address.',
                                'exemple' => 'john.doe@exemple.com'
                            ],
                        ]
                    ],
                    [
                        'label' => "Account",
                        'metaKeys' => [
                            [
                                'label' => 'User login',
                                'value' => 'user_login',
                                'source' => 'user',
                                'description' => 'User\'s login username.',
                                'exemple' => 'john_doe'
                            ],
                            [
                                'label' => 'User id',
                                'value' => 'ID',
                                'source' => 'user',
                                'description' => 'Unique identifier for each user in WordPress.',
                                'exemple' => '123'
                            ],
                            [
                                'label' => 'Wordpress capabilities',
                                'value' => 'wp_capabilities',
                                'source' => 'user',
                                'description' => 'Stores user roles and capabilities for access permissions.',
                                'exemple' => 'a:1:{s:8:"customer";b:1;}'
                            ],
                            [
                                'label' => 'Description',
                                'value' => 'description',
                                'source' => 'user',
                                'description' => 'Optional description of the user.',
                                'exemple' => 'A WordPress enthusiast.'
                            ],
                            [
                                'label' => 'User_url',
                                'value' => 'user_url',
                                'source' => 'user',
                                'description' => 'User\'s website URL.',
                                'exemple' => 'https://www.exemple.com'
                            ],
                            [
                                'label' => 'User status',
                                'value' => 'user_status',
                                'source' => 'user',
                                'description' => 'User\'s status in the system (e.g., 0 for active, 1 for inactive).',
                                'exemple' => '0'
                            ],
                            // [
                            //     'label' => 'session_tokens',
                            //     'value' => 'session_tokens',
                            //     'description' => 'Stores session information, including tokens for maintaining login.'
                            //     'exemple' => '{"ABCD1234": {"expiration": "1644576000"'
                            // },
                        ]
                    ],
                ],
                'allowedSource' => [W2P_HOOK_SOURCES["user"], W2P_HOOK_SOURCES["order"]]
            ],
            [
                'label' => "Environment Data",
                'description' => null,
                'toolTip' => null,
                'subcategories' => [
                    [
                        'label' => "",
                        'metaKeys' => [
                            [
                                'label' => 'Current time',
                                'value' => 'w2p_current_time',
                                'source' => 'w2p',
                                'description' => 'Current time when the query is sent to pipedrive',
                                'recommanded' => false,
                                'exemple' => '2024-09-13 15:30:45'
                            ],
                            [
                                'label' => 'Current date',
                                'value' => 'w2p_current_date',
                                'source' => 'w2p',
                                'description' => 'Current date when the query is sent to pipedrive',
                                'recommanded' => false,
                                'exemple' => '2024-09-13'
                            ],
                            [
                                'label' => 'Website domain',
                                'value' => 'w2p_website_domain',
                                'source' => 'w2p',
                                'description' => 'Current Website domain',
                                'recommanded' => false,
                                'exemple' => 'mywebsite.com'
                            ],
                            [
                                'label' => 'Site title',
                                'value' => 'w2p_site_title',
                                'source' => 'w2p',
                                'description' => 'Title of the current website',
                                'recommanded' => false,
                                'exemple' => 'My Awesome Website'
                            ],
                        ]
                    ],
                ],
                'allowedSource' => array_values(W2P_HOOK_SOURCES) //all
            ],
        ]
    );
}

if (!defined("W2P_QUERY_CATEGORY_TYPE")) {
    define("W2P_QUERY_CATEGORY_TYPE", [
        "person" => "user",
        "organization" => "user",
        "deal" => "order",
    ]);
}

//KEEP THIS ORDER!!
if (!defined("W2P_CATEGORY")) {
    define("W2P_CATEGORY", [
        "organization" => "organization",
        "person" => "person",
        "deal" => "deal",
    ]);
}


if (!defined("W2P_REQUIRED_FIELDS")) {
    define(
        "W2P_REQUIRED_FIELDS",
        [
            "deal" => ["title"],
            "person" => ["name"],
            "organization" => ["name"]
        ]
    );
}

if (!defined("W2P_ORDER_STATUS_HOOK")) {
    define(
        "W2P_ORDER_STATUS_HOOK",
        [
            'on-hold' => 'woocommerce_order_status_on-hold',
            'pending' => 'woocommerce_order_status_pending',
            'processing' => 'woocommerce_order_status_processing',
            'completed' => 'woocommerce_order_status_completed',
            'refunded' => 'woocommerce_order_status_refunded',
            'cancelled' => 'woocommerce_order_status_cancelled',
            'failed' => 'woocommerce_order_status_failed',
        ]
    );
}

if (!defined("W2P_EMPTY_SYNC_ADDITIONAL_DATA")) {
    define(
        "W2P_EMPTY_SYNC_ADDITIONAL_DATA",
        [
            "total_users" => 0,
            "current_user" => 0,
            "total_orders" => 0,
            "current_order" => 0,
            "current_user_index" => 0,
            "current_order_index" => 0,
            "total_person_errors" => 0,
            "total_person_uptodate" => 0,
            "total_person_done" => 0,
            "total_order_errors" => 0,
            "total_order_uptodate" => 0,
            "total_order_done" => 0,
        ]
    );
}


if (!defined("W2P_HOOK_LIST")) {
    define(
        "W2P_HOOK_LIST",
        [
            [
                "label" => "User login",
                "key" => "wp_login",
                "description" => "Fired after a user logs in.",
                "disabledFor" => ["deal"],
                "source" => "user",
            ],
            [
                "label" => "User Registration",
                "key" => "user_register",
                "description" => "Triggered after a new user registration.",
                "disabledFor" => ["deal"],
                "source" => "user",
            ],
            [
                "label" => "User updated",
                "key" => "profile_update",
                "description" => "Fired after a user is updated.",
                "disabledFor" => ["deal"],
                "linked_hooks_key" => ["woocommerce_checkout_update_user_meta"],
                "source" => "user",
            ],
            [
                "label" => "Cart updated",
                "key" => "woocommerce_cart_updated",
                "description" => "Fired when a product is added, removed or updated to the shopping cart.",
                "disabledFor" => [],
                "source" => "order",
            ],
            [
                "label" => "New Order",
                "key" => "woocommerce_new_order",
                "description" => "Fired when a new order is created.",
                "disabledFor" => [],
                "source" => "order",
            ],
            [
                "label" => "Order updated",
                "key" => "woocommerce_update_order",
                "description" => "Fired when an order is updated.",
                "disabledFor" => [],
                "source" => "order",
            ],
            [
                "label" => "Order on hold",
                "key" => "woocommerce_order_status_on-hold",
                "description" => "Fired when an order is placed on hold.",
                "disabledFor" => [],
                "source" => "order",
            ],
            [
                "label" => "Order pending",
                "key" => "woocommerce_order_status_pending",
                "description" => "Fired when an order is awaiting payment (pending).",
                "disabledFor" => [],
                "source" => "order",
            ],
            [
                "label" => "Order processing",
                "key" => "woocommerce_order_status_processing",
                "description" => "Fired when an order is being processed.",
                "disabledFor" => [],
                "source" => "order",
            ],
            [
                "label" => "Order completed",
                "key" => "woocommerce_order_status_completed",
                "description" => "Fired when an order is successfully completed.",
                "disabledFor" => [],
                "source" => "order",
            ],
            [
                "label" => "Order refunded",
                "key" => "woocommerce_order_status_refunded",
                "description" => "Fired when an order is refunded.",
                "disabledFor" => [],
                "source" => "order",
            ],
            [
                "label" => "Order canceled",
                "key" => "woocommerce_order_status_cancelled",
                "description" => "Fired when an order is canceled.",
                "disabledFor" => [],
                "source" => "order",
            ],
            [
                "label" => "Order failed",
                "key" => "woocommerce_order_status_failed",
                "description" => "Fired when an order fails.",
                "disabledFor" => [],
                "source" => "order",
            ]
        ]
    );
}

if (!defined("W2P_HOOK_PRIORITY")) {
    define(
        "W2P_HOOK_PRIORITY",
        [
            'organization' => 100,
            'person'       => 105,
            'deal'         => 110,
        ]
    );
}
