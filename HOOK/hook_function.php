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

//Gestion un peu étrange, gestion de la source ID ici et dans w2p_register_user_defined_hooks
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
                w2p_add_error_log("$hook_key : Nothing to update for the source id $source_id of the category $hook[category] (last query were the same)", 'w2p_register_user_defined_hooks');
                return;
            }


            $query = W2P_Query::create_query(
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

                    $priority = W2P_HOOK_PRIORITY[$category];

                    if ($hook_key === "woocommerce_cart_updated") {

                        add_action(
                            'woocommerce_add_to_cart',
                            function () use ($hook) {
                                $order_id = w2p_get_current_checkout_order_id();
                                $order_id &&
                                    w2p_handle_cart_updated(
                                        array_merge(
                                            $hook,
                                            [
                                                "label" => "Product added to cart",
                                                "key" => "woocommerce_add_to_cart",
                                                "source" => "order",
                                            ]
                                        ),
                                        $order_id
                                    );
                            },
                            $priority,
                        );
                        add_action(
                            'woocommerce_after_cart_item_quantity_update',
                            function () use ($hook) {
                                $order_id = w2p_get_current_checkout_order_id();
                                $order_id &&
                                    w2p_handle_cart_updated(
                                        array_merge(
                                            $hook,
                                            [
                                                "label" => "Product quantity updated from cart",
                                                "key" => "woocommerce_after_cart_item_quantity_update",
                                                "source" => "order",
                                            ]
                                        ),
                                        $order_id,
                                    );
                            },
                            $priority,
                        );
                        add_action(
                            'woocommerce_remove_cart_item',
                            function () use ($hook) {
                                $order_id =  w2p_get_current_checkout_order_id();
                                $order_id &&
                                    w2p_handle_cart_updated(
                                        array_merge(
                                            $hook,
                                            [
                                                "label" => "Product removed from cart",
                                                "key" => "woocommerce_remove_cart_item",
                                                "source" => "order",
                                            ]
                                        ),
                                        $order_id
                                    );
                            },
                            $priority,
                        );
                    } else {
                        add_action($hook_key, function ($entity_id) use ($hook, $hook_key) {
                            if ($hook_key === "woocommerce_update_order") {
                                if (get_current_user_id()) {
                                    $disabled = get_transient("disable_hook_update_order_$entity_id");
                                    if ($disabled) {
                                        set_transient("fired_woocommerce_update_ order_from_card_$entity_id", false);
                                    } else {
                                        w2p_handle_custom_hook($hook, $entity_id);
                                    }
                                }
                            } else {
                                w2p_handle_custom_hook($hook, $entity_id);
                            }
                        }, $priority, 1);
                        if (isset($hook["linked_hooks"])) {
                            foreach ($hook["linked_hooks_key"] as $linked_hook_key) {
                                add_action($linked_hook_key, function ($entity_id) use ($hook) {
                                    w2p_handle_custom_hook($hook, $entity_id);
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

add_action('w2p_check_last_woocommerce_update_order', 'w2p_check_last_woocommerce_update_order_handler', 10, 3);
function w2p_check_last_woocommerce_update_order_handler($hook, $order_id, $iteration)
{
    try {
        if ($hook["key"] === "woocommerce_add_to_cart") {
            w2p_create_or_update_order_from_api(); //force update order
        }

        $last_iteration = get_transient("last_iteration_fired_woocommerce_update_order_from_card_$order_id");
        if ($last_iteration == $iteration) {
            w2p_handle_custom_hook(
                $hook,
                $order_id,
            );
            set_transient("last_iteration_fired_woocommerce_update_order_from_card_$order_id", 0, 10);
        }
    } catch (Throwable $e) {
        w2p_add_error_log("Error handling wart update " . $e->getMessage(), "w2p_check_last_woocommerce_update_order_handler");
    }
}

function w2p_update_order_from_cart($order_id, $update_items = false)
{
    // try {
    //     $order = wc_get_order($order_id);
    //     $cart = WC()->cart->get_cart();
    //     if ($order && $cart) {
    //         $order->set_cart_hash(WC()->cart->get_cart_hash());

    //         if ($update_items) {
    //             foreach ($order->get_items() as $item_id => $item) {
    //                 $order->remove_item($item_id);
    //             }

    //             // 3. Ajoute les produits du panier comme items de la commande
    //             foreach ($cart as $cart_item_key => $cart_item) {
    //                 $product = wc_get_product($cart_item['product_id']);
    //                 if ($product) {
    //                     $item = new WC_Order_Item_Product();
    //                     $item->set_product($product); // Associe le produit à l'item
    //                     $item->set_quantity($cart_item['quantity']);
    //                     $item->set_subtotal($cart_item['line_subtotal']);
    //                     $item->set_total($cart_item['line_total']);
    //                     $order->add_item($item); // Ajoute l'item à la commande
    //                 }
    //             }
    //         }
    //         $order->save();
    //     }

    //     return true;
    // } catch (Throwable $e) {
    //     w2p_add_error_log("Error updating order from cart: " . $e->getMessage(), "w2p_update_order_from_cart");
    //     return false;
    // }
}
function w2p_handle_cart_updated($hook, $order_id)
{
    try {
        // set_transient("disable_hook_update_order_$order_id", false, 60);
        set_transient("disable_hook_update_order_$order_id", true, 60);
        $iteration = did_action("woocommerce_update_order");
        set_transient("last_iteration_fired_woocommerce_update_order_from_card_$order_id", $iteration, 10);

        if (!wp_next_scheduled('w2p_check_last_woocommerce_update_order', array($hook, $order_id, $iteration))) {
            wp_schedule_single_event(
                time() + 2,
                'w2p_check_last_woocommerce_update_order',
                array($hook, $order_id, $iteration)
            );
        }

        w2p_update_order_from_cart(
            $order_id,
            true
        );

        $next_event = wp_get_scheduled_event('w2p_check_last_woocommerce_update_order', array($hook, $order_id, $iteration));
        if (!$next_event) {
            w2p_add_error_log("No scheduled event for w2p_check_last_woocommerce_update_order", "w2p_handle_cart_updated");
        }
    } catch (Throwable $e) {
        w2p_add_error_log("Error in handling cart update: " . $e->getMessage(), "w2p_handle_cart_updated");
        w2p_add_error_log('Parameters passed: ' . print_r(compact('hook', 'order_id'), true), 'w2p_handle_cart_updated');
    }
}

function w2p_create_or_update_order_from_api()
{
    try {
        $order_id = null;
        $request = new WP_REST_Request('GET', '/wc/store/v1/checkout');
        $request->set_header('Nonce', wp_create_nonce('wc_store_api'));
        $response = rest_do_request($request);

        if (is_wp_error($response)) {
            w2p_add_error_log('Error API : ' . $response->get_error_message(), "w2p_create_order_from_api");
            return null;
        } else if (method_exists($response, 'is_error') && $response->is_error()) {
            w2p_add_error_log('Error in response : ' . print_r($response->get_error_message(), true), "w2p_create_order_from_api");
            return null;
        }

        if (method_exists($response, 'get_data')) {
            $data = $response->get_data();

            if (!empty($data)) {
                $order_id = $data["order_id"];
            } else {
                w2p_add_error_log('No data in response.', "w2p_create_order_from_api");
            }
        } else {
            w2p_add_error_log('Response is invalid or do not contain get_data().', "w2p_create_order_from_api");
        }
        return $order_id;
    } catch (Throwable $e) {
        w2p_add_error_log('Erreur lors de la création de la commande : ' . $e->getMessage(), 'woocommerce_api_debug');
        return null;
    }
}


function w2p_get_current_checkout_order_id()
{
    try {
        if (get_current_user_id()) {

            $order_id = WC()->session->get("store_api_draft_order");
            // $order_id = null;
            if (!$order_id) {
                $order_id = w2p_create_or_update_order_from_api();
            }

            w2p_add_error_log(print_r(WC()->session->get_session_data(), true), "woocommerce_session_debug");
            w2p_add_error_log($order_id ?? "null", "woocommerce_session_debug");

            return $order_id;
        } else {
            return null;
        }
    } catch (Throwable $e) {
        w2p_add_error_log("Error while getting order_id: " . $e->getMessage(), "w2p_manage_cart_and_order");
        return null;
    }
}

// function w2p_get_current_checkout_order_id()
// {
//     try {
//         if (get_current_user_id()) {
//             // Récupérer l'ID de commande depuis `store_api_draft_order` ou fallback
//             $order_id = WC()->session->get("store_api_draft_order") ?? WC()->session->get("order_awaiting_payment") ?? 0;

//             if ($order_id) {
//                 $order = wc_get_order($order_id);

//                 // Vérifier si la commande existe et a un statut valide
//                 if ($order && in_array($order->get_status(), ['pending', 'on-hold'], true)) {
//                     return $order_id;
//                 }

//                 // Nettoyer la session si la commande est invalide
//                 WC()->session->__unset("store_api_draft_order");
//                 WC()->session->__unset("order_awaiting_payment");
//             }

//             // Créer une nouvelle commande si aucune valide n'existe
//             $checkout = new WC_Checkout();
//             $order_id = $checkout->create_order([]);

//             WC()->session->set("store_api_draft_order", $order_id);
//             WC()->session->set("order_awaiting_payment", $order_id);



//             w2p_add_error_log(print_r(WC()->session->get_session_data(), true), "woocommerce_session_debug");
//             w2p_add_error_log($order_id, "woocommerce_session_debug");

//             return $order_id;
//         }
//     } catch (Throwable $e) {
//         w2p_add_error_log("Error while getting order_id: " . $e->getMessage(), "w2p_manage_cart_and_order");
//         return null;
//     }
// }


w2p_register_user_defined_hooks();
