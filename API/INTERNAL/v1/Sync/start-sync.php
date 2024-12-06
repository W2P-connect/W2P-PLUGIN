<?php

add_action('rest_api_init', function () {
    register_rest_route(
        'w2p/v1',
        '/start-sync',
        array(
            'methods' => 'GET',
            'callback' => 'w2p_start_sync',
            'args' => [
                're-sync' => [
                    'type' => 'boolean',
                    'default' => false,
                    'required' => false,
                ],
                'retry' => [
                    'type' => 'boolean',
                    'default' => false,
                    'required' => false,
                ],
            ],
            'permission_callback' => 'w2p_jwt_token'
        )
    );
});

add_action('w2p_cron_check_sync', function () {

    $is_sync_running = get_option('w2p_sync_running', false);
    $last_heartbeat = get_option('w2p_sync_last_heartbeat', null);

    if ($is_sync_running && (!$last_heartbeat || time() - $last_heartbeat > 60)) {
        w2p_add_error_log(
            "cron job launched: Condition du if du cron déclenché: la syncronisation est running mais pourtant pas de heart_beat" .
                print_r(
                    [
                        "is_sync_running" => $is_sync_running,
                        "last_heartbeat" => $last_heartbeat,
                        "time() - last_heartbeat > 60" => time() - $last_heartbeat > 60,
                    ],
                    true
                ),
            "w2p_cron_check_sync"
        );
        w2p_sync_function(false, true);
    }
});

function w2p_reset_sync_options()
{
    update_option('w2p_sync_additional_datas', W2P_EMPTY_SYNC_ADDITIONAL_DATA);
    update_option('w2p_sync_last_error', "");
    update_option('w2p_sync_progress_users', 0);
    update_option('w2p_sync_progress_orders', 0);
}

function w2p_start_sync(WP_REST_Request $request)
{
    try {

        $retry = $request->get_param("retry");

        if (!w2p_is_sync_running() || $retry) {

            w2p_add_error_log("starting sync ", "w2p_start_sync");

            if (!$retry) {
                w2p_reset_sync_options();
            }

            update_option('w2p_start_sync', true);

            $sync_url = rest_url('w2p/v1/run-sync');
            $args = array(
                'blocking' => false,
                'method'   => 'POST',
                'body'     => json_encode($request->get_params()),
                'headers'  => array(
                    'Content-Type' => 'application/json',
                ),
            );

            wp_remote_post($sync_url, $args);

            if (!wp_next_scheduled('w2p_cron_check_sync')) {
                wp_schedule_event(time(), 'one_minute', 'w2p_cron_check_sync');
            }

            return new WP_REST_Response(
                [
                    "success" => true,
                    "message" => "Synchronization started in the background",
                ],
                200
            );
        } else {
            return new WP_REST_Response(
                [
                    "message" => "synchronization already in progress",
                    "running" => w2p_is_sync_running(),
                ],
                200
            );
        }
    } catch (\Throwable $e) {
        // Gérer l'erreur
        return new WP_REST_Response(
            [
                "success" => false,
                "error" => $e->getMessage(),
            ],
            500
        );
    }
}
