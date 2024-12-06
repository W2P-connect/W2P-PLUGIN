<?php
// Ajout d'un nouveau point d'API pour exécuter la synchronisation
add_action('rest_api_init', function () {
    register_rest_route(
        'w2p/v1',
        '/run-sync',
        array(
            'methods' => 'POST',
            'callback' => 'w2p_run_sync',
            'permission_callback' => function () {
                return get_option('w2p_start_sync', false);
            }
        )
    );
});

function w2p_update_additional_data(string $key, $value)
{
    $w2p_sync_additional_datas = get_option("w2p_sync_additional_datas", W2P_EMPTY_SYNC_ADDITIONAL_DATA);

    $w2p_sync_additional_datas[$key] = $value;

    update_option('w2p_sync_additional_datas', $w2p_sync_additional_datas);
}

function w2p_incremente_additional_data(string $key)
{
    $w2p_sync_additional_datas = get_option("w2p_sync_additional_datas", W2P_EMPTY_SYNC_ADDITIONAL_DATA);

    $w2p_sync_additional_datas[$key] = (int) $w2p_sync_additional_datas[$key] + 1;

    update_option('w2p_sync_additional_datas', $w2p_sync_additional_datas);
}

function w2p_run_sync(WP_REST_Request $request)
{
    $resync = $request->get_param("re-sync");
    $retry = $request->get_param("retry");


    update_option('w2p_start_sync', false);

    return w2p_sync_function($resync, $retry);
}

function w2p_sync_function($resync = false, $retry = false)
{
    try {
        update_option('w2p_sync_last_heartbeat', time());

        if (!w2p_is_sync_running() || $retry) {

            update_option("w2p_sync_running", true);

            $w2p_sync_additional_datas = get_option('w2p_sync_additional_datas', W2P_EMPTY_SYNC_ADDITIONAL_DATA);
            $init_user_index = $w2p_sync_additional_datas["current_user_index"];
            $init_order_index = $w2p_sync_additional_datas["current_order_index"];

            w2p_add_error_log(
                "Démarage de la fonction w2p_run_sync avec les params suivant : "
                    . print_r(["resync" => $resync, "retry" => $retry], true)
                    . print_r($w2p_sync_additional_datas, true),
                "w2p_sync_function"
            );

            /***************** Users **************/

            $users = get_users([
                'orderby' => 'registered',
                'order' => 'ASC'
            ]);

            $total_users = count($users);

            $orders = wc_get_orders([
                'limit' => -1,
                'orderby' => 'date',
                'order' => 'ASC'
            ]);

            $total_orders = count($orders);

            w2p_update_additional_data("total_users", $total_users);
            w2p_update_additional_data("total_orders", $total_orders);


            foreach ($users as $index => $user) {
                if ($index < $init_user_index || !w2p_is_sync_running()) {
                    continue;
                }
                update_option('w2p_sync_last_heartbeat', time());
                $user = new W2P_User($user->ID);
                $skip_next_query = false;

                $hook_obj_orga = w2p_get_hook("profile_update", W2P_CATEGORY["organization"], $user->ID);
                if ($hook_obj_orga) {
                    $formated_hook = $hook_obj_orga->w2p_get_formated_hook();
                    $organization_queries = $user->get_organization_queries();

                    if (isset($organization_queries[0])) {
                        $query_payload = $organization_queries[0]["payload"];
                        unset($query_payload['data']);

                        if ($query_payload == $formated_hook) {
                            $skip_next_query = true;
                            if ($organization_queries[0]["state"] !== "DONE") {
                                $query = new W2P_query($organization_queries[0]["id"]);
                                $query->send(true);
                            }
                        }
                    }

                    if (!$skip_next_query = false) {
                        $query_obj = W2P_query::create_query(
                            $formated_hook["category"],
                            $formated_hook["source"],
                            $formated_hook["source_id"],
                            "Manual ($formated_hook[label])",
                            $formated_hook
                        );
                        $query_obj->send(true);
                    }
                    $skip_next_query = false;
                }


                $hook_obj_person = w2p_get_hook("profile_update", W2P_CATEGORY["person"], $user->ID);
                if ($hook_obj_person) {

                    $formated_hook = $hook_obj_person->w2p_get_formated_hook();
                    $person_queries = $user->get_person_queries();

                    if (isset($person_queries[0])) {
                        $query_payload = $person_queries[0]["payload"];
                        unset($query_payload['data']);

                        if ($query_payload == $formated_hook) {
                            $skip_next_query = true;
                            w2p_incremente_additional_data("total_person_uptodate");
                            if ($person_queries[0]["state"] !== "DONE") {
                                $query = new W2P_query($person_queries[0]["id"]);
                                $query->send(true);
                            }
                        }
                    }

                    if (!$skip_next_query) {
                        $query_obj = W2P_query::create_query(
                            $formated_hook["category"],
                            $formated_hook["source"],
                            $formated_hook["source_id"],
                            "Manual ($formated_hook[label])",
                            $formated_hook
                        );
                        $send_infos = $query_obj->send(true);

                        if ($send_infos["success"]) {
                            w2p_incremente_additional_data("total_person_done");
                        } else {
                            w2p_incremente_additional_data("total_person_errors");
                        }
                    }
                    $skip_next_query = false;
                } else {
                    update_option("w2p_sync_running", false);
                    w2p_add_error_log("You need to set the status 'User updated' in persons settings", "w2p_run_sync");
                    update_option('w2p_sync_last_error', "You need to enable the 'User updated' status in the person's settings.");

                    return new WP_REST_Response(
                        [
                            "success" => false,
                            "data" =>  $user->ID,
                            "message" => "You need to set the status 'User updated' in persons settings.",
                        ],
                        200
                    );
                }


                $progress = intval(($index + 1) / $total_users * 100);
                update_option('w2p_sync_progress_users', $progress);

                w2p_update_additional_data("current_user", $user->ID);
                w2p_update_additional_data("current_user_index", $index + 1);
            }

            /***************** Orders **************/

            foreach ($orders as $index => $order) {
                if ($index < $init_order_index || !w2p_is_sync_running()) {
                    continue;
                }
                update_option('w2p_sync_last_heartbeat', time());

                $skip_next_query = false;
                $order_id = $order->get_id();
                $order = new W2P_order($order_id);

                $status = $order->get_status(); //de woocomemrce
                $key = W2P_ORDER_STATUS_HOOK[$status];

                $hook_obj = w2p_get_hook($key, W2P_CATEGORY["deal"], $order_id);

                if ($hook_obj) {
                    $formated_payload = $hook_obj->w2p_get_formated_hook();

                    $order_data = $order->get_data();
                    $order_queries = $order_data["queries"];

                    if (isset($order_queries[0])) {
                        $query_payload = $order_queries[0]["payload"];
                        unset($query_payload['data']);

                        if ($query_payload == $formated_payload) {
                            $skip_next_query = true;
                            if ($order_queries[0]["state"] !== "DONE") {
                                $query = new W2P_query($order_queries[0]["id"]);
                                $query->send(true);
                            }
                        }
                    }

                    if (!$skip_next_query) {
                        $query_obj = W2P_query::create_query(
                            $formated_payload["category"],
                            $formated_payload["source"],
                            $formated_payload["source_id"],
                            "Manual ($formated_payload[label])",
                            $formated_payload
                        );

                        $send_infos = $query_obj->send(true);

                        if ($send_infos["success"]) {
                            w2p_incremente_additional_data("total_order_done");
                        } else {
                            w2p_incremente_additional_data("total_order_errors");
                        }
                    } else {
                        w2p_incremente_additional_data("total_order_uptodate");
                    }
                }


                $progress = intval(($index + 1) / $total_orders * 100);
                update_option('w2p_sync_progress_orders', $progress);

                w2p_update_additional_data("current_order", $order_id);
                w2p_update_additional_data("current_order_index", $index + 1);
            }

            update_option("w2p_sync_running", false);
            update_option("w2p_last_sync", date("d M Y H:i:s"));

            wp_clear_scheduled_hook('w2p_cron_check_sync');

            return new WP_REST_Response(
                [
                    "success" => true,
                    "message" => "Data synced",
                ],
                200
            );
        } else {

            $w2p_sync_progress_users = get_option('w2p_sync_progress_users', 0);
            $w2p_sync_progress_orders = get_option('w2p_sync_progress_orders', 0);
            $w2p_sync_additional_datas = get_option('w2p_sync_additional_datas', []);

            return new WP_REST_Response(
                [
                    "message" => "synchronization already in progress",
                    "running" => w2p_is_sync_running(),
                    "sync_progress_users" => $w2p_sync_progress_users,
                    "sync_progress_orders" => $w2p_sync_progress_orders,
                    "w2p_sync_additional_datas" => $w2p_sync_additional_datas,
                ],
                200
            );
        }
    } catch (\Throwable $e) {
        //reset
        update_option("w2p_sync_running", false);
        wp_clear_scheduled_hook('w2p_cron_check_sync');
        w2p_add_error_log("ERROR : " . $e->__toString(), "w2p_run_sync");
        update_option('w2p_sync_last_error', "An error occurred during the synchronization. Please try again later. If the issue persists, you may want to contact support for assistance.");

        return new WP_REST_Response(
            [
                "success" => false,
                "error" => $e->getMessage(),
                "traceback" => $e->__toString(),
            ],
            500
        );
    }
}
