<?php

function w2p_add_to_cart()
{

    return 1;
}

function w2p_get_customer_id_from_order_id(int $order_id): ?int
{
    try {
        $order = wc_get_order($order_id);

        if ($order instanceof \Automattic\WooCommerce\Admin\Overrides\OrderRefund) {
            // Si c’est un remboursement, récupère la commande originale
            $parent_order = wc_get_order($order->get_parent_id());
            $customer_id = $parent_order ? $parent_order->get_customer_id() : null;
        } elseif ($order instanceof \WC_Order) {
            // Si c’est une commande, récupère directement l'ID du client
            $customer_id = $order->get_customer_id();
        } else {
            return null;  // Pas de type reconnu
        }

        return $customer_id && $customer_id > 0
            ? $customer_id
            : null;
    } catch (Throwable $e) {
        w2p_add_error_log("Error retrieving customer ID from order: " . $e->getMessage(), "w2p_get_customer_id_from_order_id");
        w2p_add_error_log('Parameters passed: ' . print_r(compact('order_id'), true), 'w2p_get_customer_id_from_order_id');
        return null;
    }
}

//TODO ?
function w2p_get_customer_id_from_product_key(string $product_key): ?int
{
    return null;
}
