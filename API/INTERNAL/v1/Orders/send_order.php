<?php

add_action('rest_api_init', function () {
    register_rest_route(
        'w2p/v1',
        '/order/(?P<id>[\d]+)/send',
        array(
            array(
                'methods'             => 'PUT',
                'callback'            => 'w2p_send_order',
                'args'                => array(
                    'direct_to_pipedrive' => array(
                        'type'     => 'boolean',
                        'required' => false,
                        'default'  => true,
                    ),
                ),
                'permission_callback' => 'w2p_jwt_token',
            ),
        )
    );
});


function w2p_send_order(WP_REST_Request $request)
{

    try {
        $id = (int) $request->get_param("id");
        $order = new W2P_order($id);

        if (!$order->is_order) {
            return new WP_REST_Response(
                [
                    "success" => false,
                    "message" => "This is not a valid order id",
                ],
                400
            );
        }

        $create_query = (bool) $request->get_param("create-query");
        $order_data = $order->get_data();
        $order_queries = $order_data["queries"];


        if ($create_query) {
            $status = $order->get_status(); //de woocomemrce
            $key = W2P_ORDER_STATUS_HOOK[$status];
            $hook_obj = w2p_get_hook($key, W2P_CATEGORY["deal"], $id);

            if ($hook_obj) {
                $formated_hook = $hook_obj->w2p_get_formated_hook();

                $query_obj = W2P_query::create_query(
                    $formated_hook["category"],
                    $formated_hook["source"],
                    $formated_hook["source_id"],
                    "Manual ($formated_hook[label])",
                    $formated_hook
                );

                $send_info = $query_obj->send(true);

                return new WP_REST_Response(
                    [
                        "success" => $send_info["success"],
                        "send_info" => $send_info,
                        "data" => $order->get_data(),
                        "message" => $send_info["message"],
                    ],
                    200
                );
            } else {
                return new WP_REST_Response(
                    [
                        "success" => false,
                        "data" => $order->get_data(),
                        "message" => "You need to set the status 'Order $status' in deals settings.",
                    ],
                    200
                );
            }
        } else {
            if ($order_queries && count($order_queries)) {

                $query_to_sent = null;
                foreach ($order_queries as $order_query) {
                    if ($order_query["state"] !== "INVALID") {
                        $query_to_sent = $order_query;
                        break;
                    }
                }

                if ($query_to_sent) {
                    $query_obj = new W2P_query($query_to_sent["id"]);
                    $send_info = $query_obj->send(true);

                    return new WP_REST_Response(
                        [
                            "success" => $send_info["success"],
                            "send_info" => $send_info,
                            "data" => $order->get_data(),
                            "message" => $send_info["message"],
                        ],
                        200
                    );
                } else {
                    return new WP_REST_Response(
                        [
                            "success" => true,
                            "data" => $order->get_data(),
                            "message" => "No valid request to send for this order",
                        ],
                        200
                    );
                }
            } else {
                return new WP_REST_Response(
                    [
                        "success" => true,
                        "data" => $order->get_data(),
                        "message" => "No request to send for this order",
                        "payload" => $request->get_params()
                    ],
                    200
                );
            }
        }
    } catch (\Throwable $e) {
        w2p_add_error_log($e->getMessage(), "w2p_send_order");
        return new WP_REST_Response(
            [
                "success" => false,
                "data" => $order->get_data(),
                "message" => "An error occured during sending the order on your website",
                "payload" => $request->get_params(),
                "context" => "Catch triggered during w2p_send_order",
            ],
            500
        );
    }
}
