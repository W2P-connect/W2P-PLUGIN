<?php

add_action('rest_api_init', function () {
    register_rest_route(
        'w2p/v1',
        '/queries',
        array(
            array(
                'methods' => 'GET',
                'callback' => 'w2p_get_queries',
                'permission_callback' => 'w2p_jwt_token'
            ),
        )
    );
});

function w2p_get_queries($request)
{

    try {
        $queries = W2P_query::get_queries(
            true,
            $request->get_params(),
            (int) $request->get_param('page') ?? 1,
            (int) $request->get_param('per_page') ?? 10
        );

        return new WP_REST_Response(
            $queries,
            200
        );
    } catch (\Throwable $e) {
        w2p_add_error_log($e->getMessage(), 'w2p_get_queries');
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
