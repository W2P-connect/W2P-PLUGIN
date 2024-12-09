<?php

add_action('rest_api_init', function () {
    register_rest_route(
        'w2p/v1',
        '/sync-progress',
        array(
            'methods' => 'GET',
            'callback' => 'w2p_get_sync_progress',
            'permission_callback' => 'w2p_jwt_token'
        )
    );
});

function w2p_get_sync_progress()
{

    $w2p_sync_progress_users = get_option('w2p_sync_progress_users', 0);
    $w2p_sync_progress_orders = get_option('w2p_sync_progress_orders', 0);
    $w2p_sync_additional_datas = get_option('w2p_sync_additional_datas', []);
    $w2p_last_sync = get_option('w2p_last_sync', null);
    $is_sync_running = get_option('w2p_sync_running', "");
    $last_heartbeat = get_option('w2p_sync_last_heartbeat', null);
    $last_error = get_option('w2p_sync_last_error', "");

    //Forcing scheduling cron job - very important
    if (!wp_next_scheduled('w2p_send_queries')) {
        wp_schedule_event(time(), 'w2p_five_minutes', 'w2p_send_queries');
    }

    return new WP_REST_Response(
        [
            "running" => $is_sync_running,
            "sync_progress_users" => (float) $w2p_sync_progress_users,
            "sync_progress_orders" => (float) $w2p_sync_progress_orders,
            "last_sinced_date" => $w2p_last_sync,
            "last_heartbeat" => $last_heartbeat,
            "last_error" => $last_error,
            "sync_additional_datas" => $w2p_sync_additional_datas,
            "wp_next_scheduled('w2p_send_queries')" => gmdate("Y-m-d\TH:i:s\Z", wp_next_scheduled('w2p_send_queries')),
        ],
        200
    );
}
