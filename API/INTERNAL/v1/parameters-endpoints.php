<?php
add_action('rest_api_init', function () {
    register_rest_route(
        'w2p/v1',
        '/parameters',
        array(
            array(
                'methods' => 'GET',
                'callback' => 'w2p_get_parameters_api',
                'permission_callback' => 'w2p_jwt_token'
            ),
            array(
                'methods' => 'PUT',
                'callback' => 'w2p_put_parameters',
                'args'     => array(
                    'parameters' => [
                        'required' => false,
                        'type' => 'int',
                    ],
                ),
                'permission_callback' => 'w2p_jwt_token'
            ),
        )
    );
});
add_action('rest_api_init', function () {
    register_rest_route(
        'w2p/v1',
        '/restore-parameters',
        array(
            array(
                'methods' => 'PUT',
                'callback' => 'w2p_restore_parameters',
                'args'     => array(
                    'parameters' => [
                        'required' => false,
                        'type' => 'int',
                    ],
                ),
                'permission_callback' => 'w2p_jwt_token'
            ),
        )
    );
});


function w2p_get_parameters_api($request)
{
    $parameters = w2p_maybe_json_decode(get_option('w2p_parameters'));
    return new WP_REST_Response(["data" => $parameters], 200);
}

function w2p_restore_parameters($request)
{
    try {
        delete_option('w2p_sync_additional_datas');
        delete_option('w2p_sync_last_error');
        delete_option('w2p_sync_progress_users');
        delete_option('w2p_sync_progress_orders');
        delete_option('w2p_start_sync');
        delete_option('w2p_sync_last_heartbeat');
        delete_option('w2p_sync_running');
        delete_option('w2p_last_sync');

        return new WP_REST_Response(
            [
                "success" => true,
                "message" => "Settings restored."
            ],
            200
        );
    } catch (Throwable $e) {
        w2p_add_error_log("Error while restoring parameters: " . $e->getMessage(), "w2p_reset_parameters");
        return new WP_REST_Response(
            [
                "message" => "Error while restoring parameters: " . $e->getMessage(),
                "success" => false
            ],
            400
        );
    }
}

function w2p_put_parameters($request)
{
    try {
        $parameters = $request->get_param("parameters");

        if (isset($parameters["w2p"]["api_key"])) {
            $parameters["w2p"]["api_key"] = w2p_encrypt($parameters["w2p"]["api_key"]);
        }

        if (isset($parameters["pipedrive"]["api_key"])) {
            $parameters["pipedrive"]["api_key"] = w2p_encrypt($parameters["pipedrive"]["api_key"]);
        }

        if (isset($parameters["pipedrive"]["company_domain"])) {
            $parameters["pipedrive"]["company_domain"] = w2p_encrypt($parameters["pipedrive"]["company_domain"]);
        }

        if (is_array($parameters["w2p"]["hookList"])) {
            $parameters["w2p"]["hookList"] = array_values(array_filter($parameters["w2p"]["hookList"], function ($hook) {
                return $hook["enabled"] === true || (isset($hook["fields"]) && count($hook["fields"]));
            }));
        }

        $success = update_option("w2p_parameters", $parameters);

        $parameters = w2p_get_parameters();

        return new WP_REST_Response(
            [
                "data" => $parameters,
                "request" => $request->get_params(),
                "success" => $success,
                "token" => wp_generate_auth_cookie(get_current_user_id(), time() + 3600, 'auth'),
                "get_current_user_id()" => get_current_user_id(),
                "message" => $success
                    ? "Parameters updated"
                    : "Error while updating parameters"
            ],
            $success ? 200 : 400
        );
    } catch (Throwable $e) {
        w2p_add_error_log("Error while updating parameters: " . $e->getMessage(), "w2p_put_parameters");
        w2p_add_error_log('Parameters passed: ' . print_r($request->get_params(), true), 'w2p_put_parameters');
        return new WP_REST_Response(
            [
                "message" => "Error while updating parameters: " . $e->getMessage(),
                "success" => false
            ],
            400
        );
    }
}
