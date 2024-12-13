<?php

class W2P_Hook
{
    private $hook_from_parameters;
    private $source_id;

    public function __construct(array $hook_from_parameters, int $source_id)
    {
        try {
            if ($source_id < 0) {
                throw new \InvalidArgumentThrowable("Invalid source_id for W2P_HOOK: $source_id");
            }
            if (!isset(W2P_HOOK_SOURCES[$hook_from_parameters["source"]])) {
                throw new \InvalidArgumentThrowable("Invalid source for W2P_HOOK: $hook_from_parameters[source]");
            }

            $this->hook_from_parameters = $hook_from_parameters;
            $this->source_id = $source_id;
        } catch (\Throwable $e) {
            w2p_add_error_log("Error in W2P_Hook constructor: " . $e->getMessage(), "W2P_Hook->__construct()");
        }
    }

    public function w2p_get_formated_hook(): array
    {
        try {
            $formated_hook = [];
            $formated_hook["fields"] = [];
            foreach ($this->hook_from_parameters["fields"] as $field) {
                $formated_field = $this->w2p_format_hook_field($field);
                if ($formated_field) {
                    $formated_hook["fields"][] = $formated_field;
                }
            }

            $formated_hook["products"] = $this->get_order_products();
            $formated_hook["category"] = $this->hook_from_parameters["category"];
            $formated_hook["key"] = $this->hook_from_parameters["key"];
            $formated_hook["label"] = $this->hook_from_parameters["label"];
            $formated_hook["source"] = $this->hook_from_parameters["source"];
            $formated_hook["source_id"] = $this->source_id;
            return $formated_hook;
        } catch (\Throwable $e) {
            w2p_add_error_log("Error in w2p_get_formated_hook: " . $e->getMessage(), "W2P_Hook->w2p_get_formated_hook()");
            return [];
        }
    }

    private function get_order_products(): ?array
    {
        try {
            if (
                $this->hook_from_parameters["source"] !== "order"
                || $this->hook_from_parameters["category"] !== "deal"
                || !$this->source_id
            ) {
                return null;
            }

            $products = [];
            $parameters = w2p_get_parameters();
            $deal_parameters = $parameters["w2p"]["deal"];

            if (isset($deal_parameters["sendProducts"]) && $deal_parameters["sendProducts"]) {
                $order = wc_get_order($this->source_id);

                if ($order) {
                    foreach ($order->get_items() as $item_id => $item) {
                        $product_data = $this->format_product($item, $deal_parameters);
                        $products[] = $product_data;
                    }
                }
            }

            return $products;
        } catch (\Throwable $e) {
            w2p_add_error_log("Error in get_order_products for order ID: {$this->source_id} - " . $e->getMessage(), "W2P_Hook->get_order_products()");
            return null;
        }
    }

    public function get_same_previous_query(): null | W2P_Query
    {

        $formated_hook = $this->w2p_get_formated_hook();

        $last_query_query = W2P_Query::get_queries(
            false,
            [
                "category" => $formated_hook["category"],
                "source" => $formated_hook["source"],
                "source_id" => $formated_hook["source_id"]
            ],
            1,
            1
        )["data"];


        if (isset($last_query_query[0])) {
            $last_quey_obj = $last_query_query[0];
            $last_query_data = $last_quey_obj->get_data();
            $last_query_payload = $last_query_data["payload"];
            unset($last_query_payload['data']);
            if (
                isset($last_query_payload['fields']) &&
                $last_query_payload['fields'] == $formated_hook["fields"]
                && $last_query_payload['products'] == $formated_hook["products"]
            ) {
                return $last_quey_obj;
            }
        }
        return null;
    }


    private function format_product($item, $deal_parameters): array
    {
        try {
            $product_name = $item->get_name();  // Nom du produit
            $quantity = ((float)  $item->get_quantity())
                ? (float)  $item->get_quantity()
                : 1;

            $regular_price = (float) $item->get_subtotal() / $quantity;
            $sale_price = (float) $item->get_total() / $quantity;
            $source_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();

            // Commentaires si définis dans les paramètres
            if ($source_id) {
                $product_comment = isset($deal_parameters["productsComment"]["variables"])
                    ? w2p_format_variables(
                        $deal_parameters["productsComment"]["variables"],
                        $source_id,
                        $this->get_user_id(),
                        false
                    )
                    : "";
            } else {
                $product_comment = "";
            }

            // Classe de taxe et calcul du taux de TVA
            $tax_class = $item->get_meta('_tax_class', true);
            $tax_rates = WC_Tax::get_rates($tax_class);
            $tax_rate = !empty($tax_rates) ? reset($tax_rates)['rate'] : 0;

            // Calcul du pourcentage de réduction
            $discount_percentage = 0;
            if ($regular_price > 0 && $sale_price > 0) {
                $discount_percentage = (($regular_price - $sale_price) / $regular_price) * 100;
            }

            $currency = get_option('woocommerce_currency');
            return [
                "name" => $product_name,
                "comments" => $product_comment,
                "quantity" => $quantity,
                "tax" => $tax_rate,
                "discount" => $discount_percentage,
                "discount_type" => "percentage",
                "tax_method" => $deal_parameters["amountsAre"],
                "currency" => $currency,
                "currency_symbol" => get_woocommerce_currency_symbol($currency),
                "item_price" => $deal_parameters["amountsAre"] === "inclusive"
                    ? round($regular_price * (1 + $tax_rate / 100), 2)
                    : $regular_price,
                "prices" => [
                    "regular_price" => $regular_price,
                    "sale_price" => $sale_price,
                ]
            ];
        } catch (\Throwable $e) {
            w2p_add_error_log("Error in format_product: " . $e->getMessage(), "W2P_Hook->format_product()");
            return [];
        }
    }

    private function w2p_format_hook_field(array $field): ?array
    {
        try {
            if ($field["enabled"]) {
                if (w2p_is_logic_block_value($field["value"])) {
                    return [
                        "pipedriveFieldId" => $field["pipedriveFieldId"],
                        "condition" => $field["condition"],
                        "values" => w2p_format_logic_blocks($field["value"], $this->source_id, $this->get_user_id()),
                        "isLogicBlock" => true,
                    ];
                } else {
                    return [
                        "pipedriveFieldId" => $field["pipedriveFieldId"],
                        "condition" => $field["condition"],
                        "values" => [$field["value"]],
                        "isLogicBlock" => false,
                    ];
                }
            } else {
                return null;
            }
        } catch (\Throwable $e) {
            w2p_add_error_log("Error in w2p_format_hook_field: " . $e->getMessage(), "W2P_Hook->w2p_format_hook_field()");
            w2p_add_error_log("Parameters passed: " . print_r($field, true), "W2P_Hook->format_product()");
            return null;
        }
    }

    private function get_user_id(): ?int
    {
        try {
            if ($this->hook_from_parameters["source"] === W2P_HOOK_SOURCES["user"]) {
                return $this->source_id;
            }
            if ($this->hook_from_parameters["source"] === W2P_HOOK_SOURCES["order"]) {
                return w2p_get_customer_id_from_order_id($this->source_id);
            }
            if ($this->hook_from_parameters["source"] === W2P_HOOK_SOURCES["product"]) {
                return w2p_get_customer_id_from_product_key($this->source_id);
            }
            return null;
        } catch (\Throwable $e) {
            w2p_add_error_log("Error in get_user_id: " . $e->getMessage(), "W2P_Hook->get_user_id()");
            return null;
        }
    }

    public function get_pipedrive_target_id()
    {
        try {
            $meta_key = w2p_get_meta_key($this->hook_from_parameters["category"], "id");
            $target_id = null;

            if (
                $this->hook_from_parameters["category"] === W2P_CATEGORY["person"]
                || $this->hook_from_parameters["category"] === W2P_CATEGORY["organization"]
            ) {
                $user = new W2P_User($this->get_user_id());
                $target_id = $user->get($meta_key, 'id');
            } else if (
                $this->hook_from_parameters["category"] === W2P_CATEGORY["deal"]
                && $this->hook_from_parameters["source"] === "order"
            ) {
                $order = wc_get_order($this->source_id);
                if ($order) {
                    $target_id = $order->get_meta($meta_key, 'id');
                }
            }

            return $target_id;
        } catch (\Throwable $e) {
            w2p_add_error_log("Error in get_pipedrive_target_id: " . $e->getMessage(), "W2P_Hook->get_pipedrive_target_id()");
            return null;
        }
    }
}
