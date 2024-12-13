<?php

add_action('rest_api_init', function () {
    register_rest_route(
        'w2p/v1',
        '/query/(?P<id>[\d]+)/',
        array(
            array(
                'methods' => 'PUT',
                'callback' => 'w2p_ext_put_query',
                'args' => [
                    'pipedrive_response' => [
                        'required' => false,
                        'type' => 'string',
                        'default' => null,
                    ],
                    'traceback' => [
                        'required' => false,
                        'type' => 'array',
                        'default' => [],
                    ],
                ],
                'permission_callback' => function ($request) {
                    return w2p_check_api_key($request["api_key"]);
                }
            ),
        )
    );
});


function w2p_ext_put_query($request)
{
    try {
        $id = (int) $request->get_param("id");
        $query = new W2P_Query((int) $id);

        if ($query->new_instance) {
            return new WP_REST_Response(
                [],
                204
            );
        } else {

            $params = $request->get_params();
            $pipedrive_response = null;
            if (isset($params['pipedrive_response']) && is_string($params['pipedrive_response'])) {
                $params['pipedrive_response'] = w2p_maybe_json_decode($params['pipedrive_response'], true);
            }

            $traceback          = $params["traceback"];
            $pipedrive_response = $params["pipedrive_response"];

            if ($traceback && is_array($traceback)) {
                foreach ($traceback as $event) {
                    if (isset($event['step']) && isset($event["success"])) {
                        $date = isset($event["createdAt"]) ? gmdate("Y-m-d\TH:i:s\Z", strtotime($event["createdAt"])) : null;

                        $query->add_traceback(
                            $event['step'],
                            $event["success"],
                            isset($event["value"]) ? $event["value"] : "",
                            isset($event["data"]) ? $event["data"] : "",
                            false,
                            $date,
                        );
                    }
                }
            }
            if ($pipedrive_response) {
                $query->setter("pipedrive_response", $pipedrive_response);
                if (isset($pipedrive_response["id"])) {
                    $query->setter('target_id', $pipedrive_response["id"]);
                    $query->update_source_target_id($pipedrive_response["id"]);
                    $query->cancel_previous_query();
                }
            }

            return new WP_REST_Response(
                [
                    "success" => true,
                    "message" => "Query updated",
                    "data" => $query->get_data(),
                    "params" => $params,
                ],
                200
            );
        }
    } catch (\Throwable $e) {
        w2p_add_error_log($e->getMessage(), 'w2p_ext_put_query');
        return new WP_REST_Response(
            [
                "success" => false,
                "message" => $e->getMessage(),
                "params" => $params,
            ],
            500
        );
    }
}
