<?php

add_action('rest_api_init', function () {
    register_rest_route('w2p/v1', '/orders', [
        'methods' => 'GET',
        'callback' => 'w2p_get_orders',
        'permission_callback' => 'w2p_jwt_token',
    ]);
});


function w2p_get_orders(WP_REST_Request $request)
{
    try {

        $order_id = (int) $request->get_param('orderId');
        $page = (int) $request->get_param('page');
        $per_page = (int) $request->get_param('per_page');
        
        if (w2p_is_woocomerce_active()) {
            $orders = W2P_Order::get_orders(
                $order_id ? $order_id : null,
                $page ? $page : null,
                $per_page ? $per_page : null,
            );

            return new WP_REST_Response(
                $orders,
                200
            );
        } else {
            return new WP_REST_Response(
                [
                    "success" => false,
                    "data" => [],
                    "message" => "Woocomerce is not active on your website"
                ],
                200
            );
        }
    } catch (\Throwable $e) {
        w2p_add_error_log($e->getMessage(), 'w2p_get_orders');
        return new WP_REST_Response(
            [
                "success" => false,
                "error" => $e->getMessage(),
                "traceback" => $e->__toString(),
                "payload" => $request->get_params(),
            ],
            500
        );
    }
}
