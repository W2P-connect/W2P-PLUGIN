<?php


function w2p_is_logic_block_value($value)
{
    try {
        if (!is_array($value)) {
            return false;
        } else {
            foreach ($value as $subValue) {
                if (!is_array($subValue) || (is_array($subValue) && !array_key_exists("variables", $subValue))) {
                    return false;
                }
            }
            return true;
        }
    } catch (Throwable $e) {
        w2p_add_error_log('Error in w2p_is_logic_block_value: ' . $e->getMessage(), 'w2p_is_logic_block_value');
        w2p_add_error_log('Parameter passed: ' . print_r($value, true), 'w2p_is_logic_block_value');
        return false;
    }
}


/**
 * Format value of variables value in a hook field
 *
 * @param array $value
 * @return array
 */
function w2p_format_logic_blocks(array $logic_blocks, $source_id, $user_id): array
{
    try {
        $formated_logic_blocks = [];

        foreach ($logic_blocks as $sub_block) {
            $formated_values = [];
            foreach ($sub_block["variables"] as $variable) {
                $value = w2p_get_variable_value($variable, $source_id, $user_id);
                $formated_values[] = $value;
            }
            $formated_logic_blocks[] = $formated_values;
        }
        return $formated_logic_blocks;
    } catch (Throwable $e) {
        w2p_add_error_log('Error in w2p_format_logic_blocks: ' . $e->getMessage(), 'w2p_format_logic_blocks');
        w2p_add_error_log('Parameters passed: ' . print_r(compact('logic_blocks', 'source_id', 'user_id'), true), 'w2p_format_logic_blocks');
        return [];
    }
}

function w2p_format_variables(array $variables_array, $source_id, $user_id, $to_array = true): array | string
{
    try {
        $formated_values = [];

        foreach ($variables_array as $variable) {
            $value = w2p_get_variable_value($variable, $source_id, $user_id);
            $formated_values[] = $value;
        }

        $filtered_values = array_filter($formated_values, function ($value) {
            return $value !== "" || $value !== null;
        });

        if (!$to_array) {
            $filtered_values = implode(" ", $filtered_values);
        }
        return $filtered_values;
    } catch (Throwable $e) {
        w2p_add_error_log('Error in w2p_format_variables: ' . $e->getMessage(), 'w2p_format_variables');
        w2p_add_error_log('Parameters passed: ' . print_r(compact('variables_array', 'source_id', 'user_id', 'to_array'), true), 'w2p_format_variables');
        return $to_array ? [] : '';
    }
}


function w2p_get_variable_value(array $variable, ?int $source_id, ?int $user_id)
{
    try {
        if (isset($variable["isFreeField"]) && $variable["isFreeField"]) {
            return $variable['value'];
        } else {
            $value = null;
            if ($variable["source"] === W2P_HOOK_SOURCES["user"]) {
                if ($user_id) {
                    $user = new W2P_User($user_id);
                    if ($user) {
                        $value = $user->get($variable['value']);
                    }
                }
            } else if ($variable["source"] === W2P_HOOK_SOURCES["order"]) {
                $order = wc_get_order($source_id);
                if ($order) {
                    $value = w2p_get_order_value($order, $variable['value']);
                } else {
                    w2p_add_error_log("Order ID #$source_id is not valid while trying to wc_get_order source, searching value for "  . $variable["value"], "w2p_get_variable_value");
                }
            } else if ($variable["source"] === W2P_HOOK_SOURCES["product"]) {
                $product = wc_get_product($source_id);
                if ($product) {
                    $value = w2p_get_product_value($product, $variable['value']);
                } else {
                    w2p_add_error_log("Product ID #$source_id is not valid while trying to wc_get_product, searching value for "  . $variable["value"], "w2p_get_variable_value");
                }
                //Faked meta
            } else if ($variable["source"] === "w2p") {
                $value = w2p_get_w2p_value($variable['value']);
            }

            // w2p_add_error_log(print_r($variable, true) . " -> $value", "w2p_get_variable_value");
            return $value;
        }
    } catch (Throwable $e) {
        w2p_add_error_log('Error in w2p_get_variable_value: ' . $e->getMessage(), 'w2p_get_variable_value');
        w2p_add_error_log('Parameters passed: ' . print_r(compact('variable', 'source_id', 'user_id'), true), 'w2p_get_variable_value');
        return null;
    }
}

function w2p_get_w2p_value($value)
{
    try {
        switch ($value) {
            case 'w2p_current_time':
                return current_time('mysql');
            case 'w2p_current_date':
                return current_time('Y-m-d');
            case 'w2p_website_domain':
                return parse_url(home_url(), PHP_URL_HOST);
            case 'w2p_site_title':
                return get_bloginfo('name');
            default:
                throw new Throwable("Unknown meta key: $value");
        }
    } catch (Throwable $e) {
        w2p_add_error_log('Error in w2p_get_w2p_value: ' . $e->getMessage(), 'w2p_get_w2p_value');
        w2p_add_error_log('Parameters passed: ' . print_r(compact('value'), true), 'w2p_get_w2p_value');
        return null;
    }
}

function w2p_get_product_value($product, $value)
{
    try {
        switch ($value) {
            case 'id':
                return $product->get_id();
            case 'name':
                return $product->get_name();
            case 'attribute_summary':
                if ($product->is_type('variation')) {
                    return $product->get_attribute_summary();
                } else {
                    return "";
                }
            case 'slug':
                return $product->get_slug();
            case 'short_description':
                return $product->get_short_description();
            case 'price':
                return $product->get_price();
            case 'regular_price':
                return $product->get_regular_price();
            case 'sale_price':
                return $product->get_sale_price();
            case 'stock_quantity':
                return $product->get_stock_quantity();
            case 'sku':
                return $product->get_sku();
            case 'shipping_class':
                return $product->get_shipping_class();
            case 'weight':
                return $product->get_weight();
            case 'length':
                return $product->get_length();
            case 'width':
                return $product->get_width();
            case 'height':
                return $product->get_height();
            case 'tax_class':
                return $product->get_tax_class();
            case 'categories':
                return implode(', ', wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names')));
            case 'tags':
                return implode(', ', wp_get_post_terms($product->get_id(), 'product_tag', array('fields' => 'names')));
            case 'attributes':
                $attributes =  $product->get_attributes();
                return implode(', ', array_map(function ($key, $value) {
                    return "$key: $value";
                }, array_keys($attributes), $attributes));

            case 'default_attributes':
                $attributes =  $product->get_default_attributes();
                return implode(', ', array_map(function ($key, $value) {
                    return "$key: $value";
                }, array_keys($attributes), $attributes));

            default:
                // If it's a custom meta, use get_meta
                return $product->get_meta($value);
        }
    } catch (Throwable $e) {
        w2p_add_error_log('Error in w2p_get_product_value: ' . $e->getMessage(), 'w2p_get_product_value');
        w2p_add_error_log('Parameters passed: ' . print_r(compact('product', 'value'), true), 'w2p_get_product_value');
        return null;
    }
}


function w2p_get_order_value($order, $value)
{
    try {
        switch ($value) {
            case 'id':
                return $order->get_id();
            case 'billing_first_name':
                return $order->get_billing_first_name();
            case 'billing_last_name':
                return $order->get_billing_last_name();
            case 'billing_company':
                return $order->get_billing_company();
            case 'billing_email':
                return $order->get_billing_email();
            case 'billing_phone':
                return $order->get_billing_phone();
            case 'billing_address_1':
                return $order->get_billing_address_1();
            case 'billing_address_2':
                return $order->get_billing_address_2();
            case 'billing_city':
                return $order->get_billing_city();
            case 'billing_postcode':
                return $order->get_billing_postcode();
            case 'billing_country':
                return $order->get_billing_country();
            case 'billing_state':
                return $order->get_billing_state();
            case 'shipping_first_name':
                return $order->get_shipping_first_name();
            case 'shipping_last_name':
                return $order->get_shipping_last_name();
            case 'shipping_company':
                return $order->get_shipping_company();
            case 'shipping_address_1':
                return $order->get_shipping_address_1();
            case 'shipping_address_2':
                return $order->get_shipping_address_2();
            case 'shipping_city':
                return $order->get_shipping_city();
            case 'shipping_postcode':
                return $order->get_shipping_postcode();
            case 'shipping_country':
                return $order->get_shipping_country();
            case 'shipping_state':
                return $order->get_shipping_state();
            case '_order_total':
                return $order->get_total();
            case '_order_total_excl_tax':
                return $order->get_total() - $order->get_total_tax();
            case '_order_tax':
                return $order->get_total_tax();
            case '_order_shipping':
                return $order->get_shipping_total();
            case '_order_discount':
                return $order->get_total_discount(); // If a custom function is available for this
            case '_payment_method':
                return $order->get_payment_method();
            case '_order_currency':
                return $order->get_currency();
            case '_order_status':
                return $order->get_status();
            case '_shipping_method':
                return implode(', ', $order->get_shipping_methods());
            default:
                // If it's a custom meta, use get_meta
                return $order->get_meta($value);
        }
    } catch (Throwable $e) {
        w2p_add_error_log('Error in w2p_get_order_value: ' . $e->getMessage(), 'w2p_get_order_value');
        w2p_add_error_log('Parameters passed: ' . print_r(compact('order', 'value'), true), 'w2p_get_order_value');
        return null;
    }
}



function w2p_handle_custom_hook($hook, $args)
{
    try {
        $source_id = null;
        $user_id = get_current_user_id();
        $hook_key = $hook["key"];

        switch ($hook_key) {
            case 'user_register':
            case 'profile_update':
                $source_id = is_object($args) ? $args->ID : $args;
                break;

            case 'wp_login':
                $user = get_user_by('login', $args);
                if ($user) {
                    $source_id = $user->ID;
                }
                break;

            case 'woocommerce_new_order':
                $source_id = $args;
                $user_id = w2p_get_customer_id_from_order_id($source_id);
                break;

            case 'woocommerce_add_to_cart':
            case 'woocommerce_after_cart_item_quantity_update':
            case 'woocommerce_remove_cart_item':
                $source_id = $args;
                $user_id = get_current_user_id();
                break;

            default:
                if (
                    str_starts_with($hook_key, "woocommerce_order_status")
                    || $hook_key === "woocommerce_update_order"
                ) {
                    $source_id = $args;
                    $user_id = w2p_get_customer_id_from_order_id($source_id);
                } else {
                    w2p_add_error_log("/!\ Hook $hook_key ($hook[category] from $hook[source]) not recognized /!\ ", "w2p_handle_custom_hook");
                }
                break;
        }

        if ($source_id && is_int($source_id)) {
            $hook_obj = new W2P_Hook($hook, $source_id);
            $formated_hook = $hook_obj->w2p_get_formated_hook();

            $transient_key = 'w2p_hook_' . md5($formated_hook["category"] . '_' . $formated_hook["source"] . '_' . $formated_hook["source_id"] . '_' . $formated_hook["label"]);
            $previous_data = get_transient($transient_key);

            
            if ($hook_obj->get_same_previous_query()) {
                w2p_add_error_log("Nothing to update for the source id $hook[source_id] of the category $hook[category] (last query were the same)", 'w2p_register_user_defined_hooks');
                return;
            }


            $query = W2P_query::create_query(
                $formated_hook["category"],
                $formated_hook["source"],
                $formated_hook["source_id"],
                $formated_hook["label"],
                $formated_hook
            );

            w2p_add_error_log("Hook $hook_key (category: $hook[category]) triggered for source: $source_id (source: $formated_hook[source]), user: $user_id", "w2p_handle_custom_hook");
        } else {
            $stringified_args = print_r($args, true);
            w2p_add_error_log("/!\ Unable to retrieve ID for hook $hook_key ($hook[category]) triggered for source: $source_id ($hook[source]) /!\ : \n$stringified_args", "w2p_handle_custom_hook");
        }
    } catch (Throwable $e) {
        w2p_add_error_log('Error in w2p_handle_custom_hook: ' . $e->getMessage(), 'w2p_handle_custom_hook');
        w2p_add_error_log('Parameters passed: ' . print_r(compact('hook', 'args'), true), 'w2p_handle_custom_hook');
    }
}

function w2p_find_reference_hook($key)
{
    foreach (W2P_HOOK_LIST as $hook) {
        if ($hook['key'] === $key) {
            return $hook;
        }
    }
    return null;
}

function w2p_get_hook(string $key, string $category, int $source_id)
{
    $parameters = w2p_get_parameters();
    $hooks = isset($parameters["w2p"]["hookList"])
        ? $parameters["w2p"]["hookList"]
        : [];

    if ($source_id && in_array($category, W2P_CATEGORY)) {
        foreach ($hooks as $hook) {
            if (
                isset($hook["enabled"])
                && $hook["enabled"]
                && $hook["key"] === $key
                && $hook["category"] === $category
            ) {
                return new W2P_Hook($hook, $source_id);
            }
        }
    }

    return null;
}

function w2p_register_user_defined_hooks()
{
    $parameters = w2p_get_parameters();
    $hooks = isset($parameters["w2p"]["hookList"]) ? $parameters["w2p"]["hookList"] : [];

    foreach ($hooks as $hook) {
        try {
            if (isset($hook["enabled"]) && $hook["enabled"]) {
                $hook_key = $hook["key"];
                $category = $hook["category"];
                $reference = w2p_find_reference_hook($hook['key']);

                if ($reference !== null) {
                    $hook = array_merge($hook, $reference);


                    // Définir la priorité en fonction de la catégorie
                    $priority = match ($category) {
                        'organization' => 100,
                        'person'       => 105,
                        'deal'         => 110,
                        default        => 10,
                    };

                    if ($hook_key === "woocommerce_cart_updated") {
                        add_action(
                            'woocommerce_add_to_cart',
                            function ($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) use ($hook) {
                                w2p_handle_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data, $hook);
                            },
                            $priority,
                            6
                        );
                        add_action(
                            'woocommerce_after_cart_item_quantity_update',
                            function ($cart_item_key, $quantity, $old_quantity, $cart_item) use ($hook) {
                                w2p_handle_cart_item_update($cart_item_key, $quantity, $old_quantity, $cart_item, $hook);
                            },
                            $priority,
                            4
                        );
                        add_action(
                            'woocommerce_remove_cart_item',
                            function ($cart_item_key) use ($hook) {
                                w2p_handle_cart_item_remove($cart_item_key, $hook);
                            },
                            $priority,
                            1
                        );
                    } else {
                        // Enregistrer chaque hook avec une priorité spécifique
                        add_action($hook_key, function ($entity_id) use ($hook) {
                            w2p_handle_custom_hook($hook, $entity_id,);
                        }, $priority, 1);
                        if (isset($hook["linked_hooks"])) {
                            foreach ($hook["linked_hooks_key"] as $linked_hook_key) {
                                add_action($linked_hook_key, function ($entity_id) use ($hook) {
                                    w2p_handle_custom_hook($hook, $entity_id,);
                                }, $priority, 1);
                            }
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            w2p_add_error_log("Error in registering hook: " . $e->getMessage(), "w2p_register_user_defined_hooks");
        }
    }
}



w2p_register_user_defined_hooks();

function w2p_handle_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data, $hook)
{
    w2p_handle_custom_hook(
        array_merge($hook, ["label" => "Product added to cart", "key" => "woocommerce_add_to_cart"]),
        $variation_id ? $variation_id :  $product_id,
    );
}

function w2p_handle_cart_item_update($cart_item_key, $quantity, $old_quantity, $cart_item, $hook)
{
    try {
        $cart = WC()->cart->get_cart();

        if ($quantity !== $old_quantity) {
            if (isset($cart[$cart_item_key])) {
                // Récupérer les informations du produit
                $cart_item = $cart[$cart_item_key];
                $product_id = $cart_item['product_id']; // ID du produit
                $variation_id = $cart_item['variation_id']; // ID de la variation (0 si ce n'est pas une variation)

                w2p_handle_custom_hook(
                    array_merge($hook, ["label" => "Product quantity updated from cart", "key" => "woocommerce_after_cart_item_quantity_update"]),
                    $variation_id ? $variation_id :  $product_id,
                );
            }
        }
    } catch (Throwable $e) {
        w2p_add_error_log("Error in handling cart item update: " . $e->getMessage(), "w2p_handle_cart_item_update");
        w2p_add_error_log('Parameters passed: ' . print_r(compact('hook', 'cart_item_key', 'quantity', 'old_quantity', 'product_id', 'variation_id'), true), 'w2p_handle_cart_item_update');
    }
}

function w2p_handle_cart_item_remove($cart_item_key, $hook)
{
    try {
        $cart = WC()->cart->get_cart();

        if (isset($cart[$cart_item_key])) {
            // Récupérer les informations du produit
            $cart_item = $cart[$cart_item_key];
            $product_id = $cart_item['product_id']; // ID du produit
            $variation_id = $cart_item['variation_id']; // ID de la variation (0 si ce n'est pas une variation)

            w2p_handle_custom_hook(
                array_merge($hook, ["label" => "Product removed from cart", "key" => "woocommerce_remove_cart_item"]),
                $variation_id ? $variation_id :  $product_id,
            );
        }
    } catch (Throwable $e) {
        w2p_add_error_log("Error in handling cart item removal: " . $e->getMessage(), "w2p_handle_cart_item_remove");
        w2p_add_error_log('Parameters passed: ' . print_r(compact('hook', 'cart_item_key', 'product_id', 'variation_id'), true), 'w2p_handle_cart_item_remove');
    }
}
