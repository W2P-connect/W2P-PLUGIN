<?php
class W2P_Query
{
    use W2P_SetterTrait;
    use W2P_Formater;

    /**
     * @var array{
     *     id: int,
     *     target_id: int|null,
     *     source: string,
     *     source_id: int,
     *     category: string,
     *     method: string,
     *     hook: string,
     *     payload: array,
     *     state: string,
     *     pipedrive_response: array,
     *     additional_data: array
     * }
     */
    private $data = array(
        "id"                 => 0,
        "category"           => '',
        "target_id"          => 0,
        "hook"               => '',
        "method"             => '',
        "payload"            => [],
        "state"              => '',
        "source_id"          => 0,
        "pipedrive_response" => [],
        "additional_data"    => [],
        "source"             => '',
        "user_id"            => 0,
    );


    private $db_name;
    public $new_instance;

    static public $avaible_state = array(
        "CANCELED",
        "INVALID",
        "TODO",
        "SENDED",
        "ERROR",
        "DONE",
    );

    public function __construct(int $id = 0)
    {
        global $wpdb;
        $this->db_name = $wpdb->prefix . "w2p_query";
        if ($id > 0) {
            $this->load_object_from_DB($id);
        }
    }

    private function is_savable(): bool
    {
        return $this->data["category"]
            && $this->data["source_id"];
    }

    public function get_data(): array
    {
        $target_id =  $this->get_pipedrive_target_id();

        $data = $this->data;
        $data["payload"]['data'] = $this->get_payload_data();
        $data["is_valid"] = $this->is_valid();
        $data["additional_data"] = $this->getter("additional_data");
        $data["additional_data"]["last_error"] = $this->get_last_error();
        $data["state"] = $this->get_state($data);
        $data["can_be_sent"] = $this->can_be_sent($data["state"]);
        $data["target_id"] = $target_id;
        $data["user_id"] = $this->get_user_id();
        $data["method"] = $this->get_method();




        // if ($data["category"] === "deal") {
        //     w2p_add_error_log(print_r($data, true), "get_data");
        // }

        return $data;
    }

    private function get_user_id(): ?int
    {
        $user_id = null;

        if ($this->data["source"] === W2P_HOOK_SOURCES["user"]) {
            $user_id = (int) $this->data["source_id"];
        } else if ($this->data["source"] === W2P_HOOK_SOURCES["order"]) {
            $user_id = w2p_get_customer_id_from_order_id($this->data["source_id"]);
        }

        $this->data["user_id"] = $user_id;
        return $user_id;

        //TODO
        // if ($this->data["source"] === W2P_HOOK_SOURCES["product"]) {
        //     return w2p_get_customer_id_from_product_key($this->data["source_id"]);
        // }

        // return null;
    }

    public function get_user_data(): ?array
    {
        $user_data = null;

        $user_id = $this->get_user_id();

        $user = get_user_by('id', $user_id);

        if ($user) {
            $user_data = [
                'ID' => $user->ID,
                'user_login' => $user->user_login,
                'user_email' => $user->user_email,
                'user_nicename' => $user->user_nicename,
                'display_name' => $user->display_name,
                'user_meta' => get_user_meta($user_id)
            ];
        }
        // if (class_exists('WC_Order')) {
        //     $order = wc_get_order($data["source_id"]);

        //     if ($order) {
        //         $meta_data = [
        //             'order_id' => $order->get_id(),
        //             'order_date' => $order->get_date_created(),
        //             'billing_email' => $order->get_billing_email(),
        //             'billing_phone' => $order->get_billing_phone(),
        //             'shipping_address' => $order->get_formatted_shipping_address(),
        //             'billing_address' => $order->get_formatted_billing_address(),
        //             'order_meta' => $order->get_meta_data()
        //         ];
        //     }
        // }


        return $user_data;
    }


    public function __destruct()
    {
        if ($this->is_savable()) {
            $this->save_to_database();
        }
    }


    /**
     * Retrieves queries from the database based on various filters.
     *
     * @param bool $data Whether to return the full data of each query or just the query object.
     * @param array $filters Associative array of filters:
     *                       - 'state' (string|array|null): A single state or an array of states to filter by (uses OR for multiple states).
     *                       - 'method' (string|null): The HTTP method (POST, PUT).
     *                       - 'hook' (string|null): The hook name.
     *                       - 'category' (string|null): The category of the query.
     *                       - 'source_id' (int|null): The source ID to filter by.
     *                       - 'source' (string|null): The source (e.g., product, user, order).
     *                       - 'target_id' (int|null): The target ID.
     *                       - 'user_id' (int|null): The user ID.
     * @param int $page The page number for pagination.
     * @param int $per_page The number of results per page. Set to -1 for no pagination.
     * @param string $order The order of the results. Use 'DESC' for descending (default) or 'ASC' for ascending.
     * @return array Array containing the filtered query data and pagination info.
     */
    static public function get_queries(
        bool $data = true,
        array $filters = [],
        int $page = 1,
        int $per_page = 10,
        string $order = 'DESC',
    ): array {
        try {
            global $wpdb;
            $db_name = $wpdb->prefix . "w2p_query";

            $query_count = "SELECT COUNT(*) FROM $db_name WHERE 1=1";
            $query_data = "SELECT id FROM $db_name WHERE 1=1";
            $params = [];

            // Handle 'state' filter separately for single state or array of states
            if (!empty($filters['state'])) {
                if (is_array($filters['state'])) {
                    // If state is an array, build a query with OR for each state
                    $placeholders = implode(', ', array_fill(0, count($filters['state']), '%s'));
                    $query_count .= " AND `state` IN ($placeholders)";
                    $query_data .= " AND `state` IN ($placeholders)";
                    $params = array_merge($params, $filters['state']);
                } else {
                    // If state is a single string, proceed as normal
                    $query_count .= " AND `state` = %s";
                    $query_data .= " AND `state` = %s";
                    $params[] = $filters['state'];
                }
            }

            // Loop through the other filters and add them dynamically to the SQL query
            foreach (['method', 'hook', 'category', 'source_id', 'source', 'target_id', 'user_id'] as $filter) {
                if (!empty($filters[$filter])) {
                    $query_count .= " AND `$filter` = %s";
                    $query_data .= " AND `$filter` = %s";
                    $params[] = $filters[$filter];
                }
            }

            // Calculate pagination
            if ($per_page !== -1) {
                $total_items = $wpdb->get_var($wpdb->prepare($query_count, $params));
                $offset = ($page - 1) * $per_page;
                $query_data .= " ORDER BY id $order LIMIT %d, %d";
                $params[] = $offset;
                $params[] = $per_page;
            } else {
                $total_items = $wpdb->get_var($wpdb->prepare($query_count, $params));
                $query_data .= " ORDER BY id $order";
            }


            $query_data = $wpdb->prepare($query_data, $params);
            $results = $wpdb->get_results($query_data);
            $ids = wp_list_pluck($results, 'id');
            $w2p_queries = [];

            foreach ($ids as $id) {
                $data
                    ? $w2p_queries[] = (new W2P_Query($id))->get_data()
                    : $w2p_queries[] = new W2P_Query($id);
            }

            // Handle pagination
            $total_pages = $per_page !== -1 ? ceil($total_items / $per_page) : 1;
            $has_next_page = $page < $total_pages;

            return array(
                'data' => $w2p_queries,
                'pagination' => array(
                    'total_items' => $total_items,
                    'total_pages' => $total_pages,
                    'has_next_page' => $has_next_page
                ),
                'error' => null // Pas d'erreur
            );
        } catch (\Throwable $e) {
            // En cas d'erreur, renvoyer une structure similaire avec des données vides
            return array(
                'data' => [],
                'pagination' => array(
                    'total_items' => 0,
                    'total_pages' => 0,
                    'has_next_page' => false
                ),
                'error' => $e->getMessage() // Message d'erreur
            );
        }
    }




    static public function create_query(
        string $category,
        string $source,
        int $source_id,
        string $hook,
        array $payload = [],
    ): W2P_Query | bool {
        $W2P_Query = new W2P_Query();
        $W2P_Query->setter('category', $category);
        $W2P_Query->setter('source', $source);
        $W2P_Query->setter('source_id', $source_id);
        $W2P_Query->setter('hook', $hook);
        $W2P_Query->setter('payload', $payload);
        $W2P_Query->setter('state', "TODO");

        $W2P_Query->update_additionnal_data("created_at", gmdate("Y-m-d\TH:i:s\Z"));


        global $wpdb;
        $wpdb->insert($W2P_Query->db_name, $W2P_Query->format_object_for_DB());
        if ($wpdb->last_error) {
            return false;
        } else {
            $id = $wpdb->insert_id;
            $W2P_Query->setter('id', $id);
            $W2P_Query->cancel_previous_query();
            return $W2P_Query;
        }
    }

    public function update_additionnal_data($key, $value): void
    {
        $additional_data = $this->getter("additional_data");
        $additional_data[$key] = $value;
        $this->setter("additional_data", $additional_data);
    }

    private function can_be_sent($state)
    {
        return  $state !== "INVALID" && $state !== "CANCELED" && $state !== "SENDED";
    }

    public function cancel()
    {
        $this->data["state"] = "CANCELED";
        $this->save_to_database();
        return true;
    }

    public function data_for_w2p()
    {
        $parameters = w2p_get_parameters();
        return [
            "query" => $this->get_data(),
            "user_data" => $this->get_user_data(),
            "pipedrive_parameters" => [
                "domain" => w2p_get_pipedrive_domain(),
                "api_key" => w2p_get_pipedrive_api_key(),
            ],
            "w2p_parameters" => $parameters["w2p"],
        ];
    }

    private function increment_error()
    {

        $current_additional_data = $this->getter('additional_data');

        $total_error = isset($current_additional_data["total_error"])
            ? (int) $current_additional_data["total_error"]
            : 0;

        $this->update_additionnal_data("total_error", (int) ($total_error + 1));

        if ((int) ($total_error + 1) >= 5) {
            $this->add_traceback(
                "Checking query",
                false,
                "Your request encountered too many errors and needs to be cancelled. You may want to check your settings"
            );
            $this->cancel();
        }
    }

    public function send(bool $direct_to_pipedrive = false): array
    {

        $this->resset_traceback();
        $this->update_additionnal_data("sended_at", null);

        $state = $this->get_state($this->data);
        $is_valid = $this->is_valid();

        if (!$this->get_id() || !$this->can_be_sent($state) || !$is_valid) {
            $this->add_traceback(
                "Sending query from your server",
                false,
                "The query is not valid",
                [
                    "get_id" => $this->get_id(),
                    "state" => $state,
                    "can_be_sent" => $this->can_be_sent($state),
                    "is_valid" => $is_valid,
                ]
            );
            $this->increment_error();
            $this->save_to_database();
            return [
                "success" => false,
                "data" => null,
                "message" => "This query is not valid.",
            ];
        }

        $this->add_traceback(
            "Sending query from your server",
            true,
            "The query is ready to be sent"
        );

        $response = W2P_curl_request(
            W2P_DISTANT_REST_URL . "/query",
            "POST",
            [
                "user_query_id" => $this->get_id(),
                "direct_to_pipedrive" => $direct_to_pipedrive,
                "api_key" => w2p_get_api_key(),
                "domain" => w2p_get_api_domain(true),
                "user_query" => $this->data_for_w2p(),
            ]
        );

        $this->update_additionnal_data("sended_at", gmdate("Y-m-d\TH:i:s\Z"));

        //Error from W2P Internal
        if ($response["status_code"] !== 201 && $response["status_code"] !== 200) {

            $message = isset($response["data"]["message"])
                ? $response["data"]["message"]
                : ($response["status_code"] === 404 || $response["status_code"] === 503
                    ?  "Servers are down for maintenance. Apologies for the inconvenience"
                    : ($response["status_code"] === 404
                        ? "Request timed out, please try again later."
                        : (isset($response["error"])
                            ? $message = $response["error"]
                            : "Unknown error")));

            $this->add_traceback(
                "Processing the request on our servers",
                false,
                $message,
            );
            $this->get_data();
            $this->increment_error();
            $this->save_to_database();
            return [
                "message" => $message,
                "data" => null,
                "post_query_response" => $response,
                "success" => false,
            ];
        }

        if ($direct_to_pipedrive) {

            $pipedrive_response = isset($response["data"]["data"]["pipedrive_response"])
                ? $response["data"]["data"]["pipedrive_response"]
                : null;

            $traceback = isset($response["data"]["data"]["Traceback"])
                ? $response["data"]["data"]["Traceback"]
                : null;

            if (isset($response["data"]["data"]["method"])) {
                $this->setter('method', $response["data"]["data"]["method"]);
            }

            if ($traceback && is_array($traceback)) {
                foreach ($traceback as $event) {
                    if (isset($event['step']) && isset($event["success"])) {
                        $date = isset($event["createdAt"]) ? gmdate("Y-m-d\TH:i:s\Z", strtotime($event["createdAt"])) : null;

                        $this->add_traceback(
                            $event['step'],
                            $event["success"],
                            isset($event["value"]) ? $event["value"] : "", // Oui c'est value et pas message, erreur de ma part flemme de changer
                            isset($event["data"]) ? $event["data"] : "",
                            false,
                            $date,
                        );
                    }
                }
            }


            if (isset($pipedrive_response["id"])) {
                $this->setter('target_id', $pipedrive_response["id"]);
                $this->update_source_target_id($pipedrive_response["id"]);
                $this->cancel_previous_query();
            }

            $this->update_additionnal_data("responded_at", gmdate("Y-m-d\TH:i:s\Z"));
            $this->setter("pipedrive_response", $pipedrive_response);
        }

        $this->get_data();
        $this->save_to_database();

        return [
            "success" => true,
            "message" => "Query sended",
            "data" => $response["data"],
            "pipedrive_response" => isset($pipedrive_response) ? $pipedrive_response : null,
            "traceback" => isset($traceback) ? $traceback : null,
            "target_id" => $this->get_pipedrive_target_id(),
        ];
    }

    /**
     * Normalement les requêtes sont envoyées à la chaines dans l'ordre chronologique mais il est possible
     * que l'utilisateur effectue une demande de requête dans un ordre non chronologique (par un 'send' manuel)
     *
     * @return void
     */
    public function cancel_previous_query()
    {
        $queries = W2P_Query::get_queries(
            false,
            [
                'state' => ["TODO", "ERROR"],
                'category' => $this->data["category"],
                'source_id' => $this->data["source_id"],
                'target_id' => $this->data["target_id"],
                'hook' => $this->data['hook'],
            ],
            1,
            -1
        )["data"];

        foreach ($queries as $query) {
            //On annule évidement pas les requêtes plus récentes
            if ($query->get_id() < $this->get_id()) {
                $query->add_traceback(
                    "Request Cancellation",
                    false,
                    "Your request has been canceled because a more recent request has already been created or sent to Pipedrive."
                );
                $query->cancel();
            }
        }
    }

    function get_payload_data(): array
    {
        $formatted_payload = (array) $this->data["payload"];

        $data = $this->get_default_payload_data();

        if (isset($formatted_payload['fields']) && is_array($formatted_payload['fields'])) {
            foreach ($formatted_payload['fields'] as $field) {
                if ($field) {

                    $field_obj = new W2P_Fields($field);
                    $pipedrive_field = $field_obj->get_field();
                    $dataKey = $pipedrive_field["key"];
                    $values = $field['values'];

                    if ($values && count($values)) {
                        $valueToAdd = null;

                        // Si la condition est désactivée, garder la première valeur
                        if (!$field['condition'] || (isset($field['condition']["logicBlock"]['enabled']) && !$field['condition']["logicBlock"]['enabled'])) {
                            $valueToAdd = $values[0];
                        } else {
                            if (
                                isset($field['condition']["logicBlock"]['fieldNumber'])
                                && $field['condition']["logicBlock"]['fieldNumber'] === "ALL"
                            ) {
                                foreach ($values as $valueSet) {

                                    $filtered_values = array_filter($valueSet, function ($value) {
                                        return $value !== "";
                                    });

                                    if (count($filtered_values) === count($valueSet)) {
                                        $valueToAdd = $valueSet;
                                        break;
                                    }
                                }
                            } else if (
                                isset($field['condition']["logicBlock"]['fieldNumber'])
                                && $field['condition']["logicBlock"]['fieldNumber'] === "1"
                            ) {
                                foreach ($values as $valueSet) {
                                    $filtered_values = array_filter($valueSet, function ($value) {
                                        return $value !== "";
                                    });

                                    if (count($filtered_values) >= 1) {
                                        $valueToAdd = $valueSet;
                                        break;
                                    }
                                }
                            }
                        }

                        if ($valueToAdd !== null) {
                            if (is_array($valueToAdd)) {
                                $filtered_values = array_filter($valueToAdd, function ($value) {
                                    return $value !== "";
                                });
                                $valueToAdd = implode(" ", $filtered_values);
                            }

                            if ($valueToAdd !== null && trim($valueToAdd) !== ""  && $dataKey !== "") {
                                $found = false;
                                foreach ($data as &$item) {
                                    if ($item['key'] === strtolower($dataKey)) {
                                        $item = [
                                            "key" => strtolower($dataKey),
                                            "name" => $pipedrive_field["name"],
                                            "value" => $valueToAdd,
                                            "condition" => isset($field["condition"]) ? $field["condition"] : null,
                                            "pipedriveFieldId" => isset($field["pipedriveFieldId"]) ? $field["pipedriveFieldId"] : null,
                                            "isLogicBlock" => $field["isLogicBlock"],
                                        ];
                                        $found = true;
                                        break;
                                    }
                                }
                                if (!$found) {
                                    $data[] = [
                                        "key" => strtolower($dataKey),
                                        "name" => $pipedrive_field["name"],
                                        "value" => $valueToAdd,
                                        "condition" => isset($field["condition"]) ? $field["condition"] : null,
                                        "pipedriveFieldId" => isset($field["pipedriveFieldId"]) ? $field["pipedriveFieldId"] : null,
                                        "isLogicBlock" => $field["isLogicBlock"],
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }

    private function get_default_payload_data(): array
    {
        $data = [];

        $parameters = w2p_get_parameters();

        if ($this->data["category"] === "person") {
            if ($parameters["w2p"]["person"]["defaultEmailAsName"]) {
                $user_data = $this->get_user_data();
                if ($user_data) {
                    $data[] = [
                        "key" => 'name',
                        "name" => 'Name',
                        "value" => $user_data["user_email"],
                        "condition" => [
                            "logicBlock" => [
                                "enabled"     => false,
                                "fieldNumber" => "1",
                            ],
                            "SkipOnExist"     => true,
                            "findInPipedrive" => true,
                        ],
                        "isLogicBlock"     => false,
                        "pipedriveFieldId" => 0,
                    ];
                }
            }

            //Ajout automatique de l'organisation à la person
            $user_id = $this->get_user_id();
            $meta_key = w2p_get_meta_key(W2P_CATEGORY["organization"], "id");
            $org_id = get_user_meta($user_id, $meta_key, true);

            if ($org_id) {
                $data[] = [
                    "key" => 'org_id',
                    "name" => 'Organization id',
                    "value" => $org_id,
                    "condition" => [
                        "logicBlock" => [
                            "enabled"     => false,
                            "fieldNumber" => "1",
                        ],
                        "SkipOnExist"     => false,
                        "findInPipedrive" => false,
                    ],
                    "isLogicBlock"     => false,
                    "pipedriveFieldId" => 0,
                ];
            }
        } elseif ($this->data["category"] === "deal") {
            if (
                isset($parameters["w2p"]["deal"]["defaultOrderName"]["variables"])
                && is_array($parameters["w2p"]["deal"]["defaultOrderName"]["variables"])
            ) {

                /****** defaultOrderName  ******/
                $values = [];
                $variables = $parameters["w2p"]["deal"]["defaultOrderName"]["variables"];
                $user_id = $this->get_user_id();

                $valueToAdd = w2p_format_variables($variables, $this->data["source_id"], $user_id, false);

                $data[] = [
                    "key" => 'title',
                    "name" => 'Title',
                    "value" => $valueToAdd,
                    "condition" => [
                        "logicBlock" => [
                            "enabled"     => false,
                            "fieldNumber" => "1",
                        ],
                        "SkipOnExist"     => true,
                        "findInPipedrive" => true,
                    ],
                    "isLogicBlock"     => true,
                    "pipedriveFieldId" => 0,
                ];
                /***************************/

                $user_data = $this->get_user_data();
                if ($user_data) {

                    $meta_key = w2p_get_meta_key(W2P_CATEGORY["organization"], 'id');


                    $org_id = isset($user_data["user_meta"][$meta_key][0])
                        ? $user_data["user_meta"][$meta_key][0]
                        : null;
                    if ($org_id) {
                        $data[] = [
                            "key" => 'org_id',
                            "name" => 'Organization id',
                            "value" => $org_id,
                            "condition" => [
                                "logicBlock" => [
                                    "enabled"     => false,
                                    "fieldNumber" => "1",
                                ],
                                "SkipOnExist"     => false,
                                "findInPipedrive" => false,
                            ],
                            "isLogicBlock"     => false,
                            "pipedriveFieldId" => 0,
                        ];
                    }

                    $meta_key = w2p_get_meta_key(W2P_CATEGORY["person"], 'id');

                    $person_id = isset($user_data["user_meta"][$meta_key][0])
                        ? $user_data["user_meta"][$meta_key][0]
                        : null;

                    if ($person_id) {
                        $data[] = [
                            "key" => 'person_id',
                            "name" => 'Person id',
                            "value" => $person_id,
                            "condition" => [
                                "logicBlock" => [
                                    "enabled"     => false,
                                    "fieldNumber" => "1",
                                ],
                                "SkipOnExist"     => false,
                                "findInPipedrive" => false,
                            ],
                            "isLogicBlock"     => false,
                            "pipedriveFieldId" => 0,
                        ];
                    }
                }
            }
        }



        return $data;
    }

    public function is_valid()
    {
        if ($this->get_method() === "POST") {
            $data = $this->get_payload_data();

            if (!count($data)) {
                $this->add_traceback("Processing data", false, "No data available for this request.");
                return false;
            }

            $searchedKey = W2P_REQUIRED_FIELDS[$this->data["category"]];

            foreach ($searchedKey as $searchKey) {
                $foundItem = array_filter($data, function ($item) use ($searchKey) {
                    return isset($item['key']) && $item['key'] === $searchKey;
                });

                if (empty($foundItem)) {
                    $this->add_traceback(
                        "Processing data",
                        false,
                        "You need at least a $searchKey"
                            . " to create this " . $this->data["category"] . "."
                    );
                    return false;
                }
            }
        }
        return true;
    }

    private function get_method(): string
    {

        if ($this->data["method"]) {
            return $this->data["method"];
        }

        $target_id = $this->get_pipedrive_target_id();
        $method = $target_id ? "PUT" : "POST";
        $this->data["method"] = $method;
        return $method;
    }

    public function get_state($data): string
    {
        if ($this->data["state"] === "CANCELED") {
            return $this->data["state"];
        }

        if ((isset($data["is_valid"]) && !$data["is_valid"]) || !$this->is_valid()) {
            $this->data["state"] = "INVALID";
            return "INVALID";
        }

        if ($this->get_last_error()) {
            $this->data["state"] = "ERROR";
            return "ERROR";
        }

        $pipedrive_response = $this->data["pipedrive_response"];
        if (count($pipedrive_response)) {
            if (isset($pipedrive_response["id"]) && $pipedrive_response["id"]) {
                $this->data["state"] = "DONE";
                return "DONE";
            }
        }

        if (isset($data["additional_data"]["sended_at"]) && $data["additional_data"]["sended_at"]) {
            $this->data["state"] = "SENDED";
            return "SENDED";
        }
        $this->data["state"] = "TODO";
        return "TODO";
    }

    public function update_source_target_id($target_id)
    {
        $source_id = $this->getter('source_id');
        $user_id = $this->get_user_id();
        $meta_key = w2p_get_meta_key(W2P_CATEGORY[$this->data["category"]], "id");

        $parameters = w2p_get_parameters();

        switch ($this->getter("category")) {
            case W2P_CATEGORY["person"]:
                //Bien que la source puissent être une commande ou un produit, 
                //C'est bien l'utilisateur que l'on souhaite mettre à jour vu que la catégory est une person ou une organsation
                update_user_meta($user_id, $meta_key, $target_id);

                if (
                    $parameters["w2p"]["person"]["linkToOrga"]
                    && isset($this->data["pipedrive_response"]["org_id"]["value"])
                ) {
                    $meta_key = w2p_get_meta_key(W2P_CATEGORY["organization"], "id");
                    update_user_meta($user_id, $meta_key, $this->data["pipedrive_response"]["org_id"]["value"]);
                }
                break;

            case W2P_CATEGORY["organization"]:
                update_user_meta($user_id, $meta_key, $target_id);
                break;

            case W2P_CATEGORY["deal"]:
                //Il n'y à que des hook de source 'order' pour la catégorie deal. 
                //Source_id est donc forcément une commande
                update_post_meta($source_id, $meta_key, $target_id);
                break;
        }
    }

    public function get_last_error(): ?string
    {
        $last_error = null;
        $additional_data = $this->getter('additional_data');

        $traceback = isset($additional_data["traceback"])
            ? $additional_data["traceback"]
            : [];

        foreach ($traceback as $trace) {
            if (isset($trace["success"]) && $trace["success"] === false) {
                $last_error = $trace["message"];
                break;
            }
        }

        return $last_error;
    }

    public function get_pipedrive_target_id(): ?int
    {
        if ($this->data["target_id"]) {
            return $this->data["target_id"];
        }

        $target_id = null;
        $meta_key = w2p_get_meta_key($this->data["category"], "id");

        if (!(int) $this->data["source_id"]) {
            $this->data["target_id"] = null;
            return $target_id;
        }

        if (
            ($this->data["category"] === W2P_CATEGORY["person"]
                || $this->data["category"] === W2P_CATEGORY["organization"])
            && (int) $this->get_user_id()
        ) {
            $user = new W2P_User((int)  $this->get_user_id());
            if ($user) {
                $target_id = $user->get($meta_key, 'id');
            }
        } else if (
            $this->data["category"] === W2P_CATEGORY["deal"]
            && $this->data["source"] === "order"
        ) {
            $target_id = get_post_meta($this->data["source_id"], $meta_key, 'id');
        }

        $this->data["target_id"] = (int) $target_id ?  (int) $target_id : null;
        return (int) $target_id ?  (int) $target_id : null;
    }

    public function resset_traceback()
    {
        $this->update_additionnal_data("traceback", []);
    }


    public function add_traceback(string $step, bool $success, string $message = "", $additional_data = null, $internal = true, $date = null)
    {
        $current_additional_data = $this->getter('additional_data');

        $traceback = isset($current_additional_data["traceback"])
            ? $current_additional_data["traceback"]
            : [];

        $found = false;

        foreach ($traceback as &$existing_traceback) {
            if ($existing_traceback['step'] === $step) {
                $existing_traceback = [
                    "date" => $date ?? gmdate("Y-m-d\TH:i:s\Z"),
                    "step" => $step,
                    "success" => $success,
                    "message" => $message,
                    "additional_data" => $additional_data,
                    "internal" => $internal,
                ];
                $found = true;
                break;
            }
        }

        if (!$found) {
            $traceback[] = [
                "date" => $date ?? gmdate("Y-m-d\TH:i:s\Z"),
                "step" => $step,
                "success" => $success,
                "message" => $message,
                "additional_data" => $additional_data,
                "internal" => $internal,
            ];
        }
        $this->update_additionnal_data("traceback", $traceback);
    }
}
