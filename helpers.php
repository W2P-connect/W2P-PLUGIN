<?php

function w2p_load_files($dossier)
{
    try {
        $contenu = scandir($dossier);
        if ($contenu === false) {
            w2p_add_error_log("Unable to scan directory: $dossier", "w2p_load_files()");
            return;
        }

        foreach ($contenu as $element) {
            if ($element !== '.' && $element !== '..') {
                $chemin = $dossier . '/' . $element;
                // Vérifier s'il s'agit d'un dossier
                if (is_dir($chemin)) {
                    w2p_load_files($chemin);
                } elseif (pathinfo($chemin, PATHINFO_EXTENSION) === 'php') {
                    if (file_exists($chemin)) {
                        require_once $chemin;
                    } else {
                        w2p_add_error_log("File not found: $chemin", "w2p_load_files()");
                    }
                }
            }
        }
    } catch (\Throwable $e) {
        w2p_add_error_log("Error in w2p_load_files: " . $e->getMessage(), "w2p_load_files()");
        w2p_add_error_log("Parameters passed: directory = $dossier", "w2p_load_files()");
    }
}

function w2p_get_parameters(): ?array
{
    try {
        $w2p_parameters = w2p_maybe_json_decode(get_option('w2p_parameters'));

        if ($w2p_parameters === null) {
            w2p_add_error_log("Failed to decode JSON for 'w2p_parameters'.", "w2p_get_parameters()");
            return null;
        }

        // Décryptage des clés API
        if (isset($w2p_parameters["pipedrive"]["api_key"])) {
            $w2p_parameters["pipedrive"]["api_key"] = w2p_decrypt($w2p_parameters["pipedrive"]["api_key"]);
            if ($w2p_parameters["pipedrive"]["api_key"] === false) {
                w2p_add_error_log("Decryption failed for Pipedrive API key.", "w2p_get_parameters()");
            }
        }

        if (isset($w2p_parameters["pipedrive"]["company_domain"])) {
            $w2p_parameters["pipedrive"]["company_domain"] = w2p_decrypt($w2p_parameters["pipedrive"]["company_domain"]);
            if ($w2p_parameters["pipedrive"]["company_domain"] === false) {
                w2p_add_error_log("Decryption failed for Pipedrive company domain.", "w2p_get_parameters()");
            }
        }

        if (isset($w2p_parameters["w2p"]["api_key"])) {
            $w2p_parameters["w2p"]["api_key"] = w2p_decrypt($w2p_parameters["w2p"]["api_key"]);
            if ($w2p_parameters["w2p"]["api_key"] === false) {
                w2p_add_error_log("Decryption failed for W2P API key.", "w2p_get_parameters()");
            }
        }

        return is_array($w2p_parameters) ? $w2p_parameters : null;
    } catch (\Throwable $e) {
        w2p_add_error_log("Error in w2p_get_parameters: " . $e->getMessage(), "w2p_get_parameters()");
        return null;
    }
}


function w2p_jwt_token($request)
{
    if (w2p_is_local_environment()) {
        return true;
    }

    $secret_key = $request->get_param('secret_key');
    if ($secret_key === W2P_ENCRYPTION_KEY || current_user_can('manage_options')) {
        return true;
    }

    return false;
}


function w2p_check_api_key($key_to_check): bool
{
    $parameters = w2p_get_parameters();
    return $parameters && isset($parameters["w2p"]["api_key"])
        ?  $parameters["w2p"]["api_key"] === $key_to_check
        : false;
}

function w2p_get_api_key(): ?string
{
    $parameters = w2p_get_parameters();
    return $parameters && isset($parameters["w2p"]["api_key"])
        ?  $parameters["w2p"]["api_key"]
        : null;
}


function w2p_get_api_domain($schema = false): ?string
{
    $parameters = w2p_get_parameters();
    if ($schema) {
        return $parameters && isset($parameters["w2p"]["domain"])
            ?  (is_ssl()
                ? "https://" . $parameters["w2p"]["domain"]
                : "http://" . $parameters["w2p"]["domain"])
            : null;
    } else {
        return $parameters && isset($parameters["w2p"]["domain"])
            ? $parameters["w2p"]["domain"]
            : null;
    }
}

function w2p_get_pipedrive_api_key(): ?string
{
    $parameters = w2p_get_parameters();
    return $parameters && isset($parameters["pipedrive"]["api_key"])
        ?  $parameters["pipedrive"]["api_key"]
        : null;
}


function w2p_get_pipedrive_domain(): ?string
{
    $parameters = w2p_get_parameters();
    return $parameters
        && isset($parameters["pipedrive"]["company_domain"])
        && $parameters["pipedrive"]["company_domain"]
        ? "https://" . $parameters["pipedrive"]["company_domain"]
        : null;
}

function w2p_maybe_json_decode($data)
{
    if (is_string($data)) {

        try {
            $decoded = json_decode($data, true);
            return (json_last_error() == JSON_ERROR_NONE) ? $decoded : $data;
        } catch (Throwable $e) {
            w2p_add_error_log($e->getMessage(), 'w2p_maybe_json_decode');
            w2p_add_error_log('Parameters passed: ' . print_r($data, true), 'w2p_get_order_value');
            return $data;
        }
    } else {
        return $data;
    }
}


function w2p_get_users_metakey()
{
    global $wpdb;
    $table_usermeta = $wpdb->prefix . 'usermeta';

    try {
        $query = $wpdb->prepare(
            "SELECT DISTINCT meta_key
            FROM $table_usermeta"
        );
        $results = $wpdb->get_results($query);
        if ($results === null) {
            w2p_add_error_log("Query failed: " . $wpdb->last_error, "w2p_get_users_metakey()");
        }
        return $results;
    } catch (\Throwable $e) {
        w2p_add_error_log("Error in w2p_get_users_metakey: " . $e->getMessage(), "w2p_get_users_metakey()");
        return null;
    }
}

function w2p_add_error_log(string $message = 'No message', string $function = '')
{
    try {
        $log_file = plugin_dir_path(__FILE__) . 'error_log.log';

        if (!is_writable(dirname($log_file))) {
            throw new Throwable("The log directory is not writable.");
        }
        $log_entry = date('Y-m-d H:i:s ');
        if ($function) {
            $log_entry .= "[$function] - ";
        }
        $log_entry .= $message . "\n";
        error_log($log_entry, 3, $log_file);
    } catch (Throwable $e) {
        w2p_add_error_log("Error: " . $e->getMessage(), "w2p_add_error_log");
    }
}

function w2p_json_to_array(string $json)
{
    try {
        $decoded_value = w2p_maybe_json_decode($json, true);
        if (is_array($decoded_value)) {
            return array_map(function ($item) {
                return is_numeric($item) ? floatval($item) : $item;
            }, $decoded_value);
        } else {
            return [];
        }
    } catch (\Throwable $e) {
        w2p_add_error_log("Error: " . $e->getMessage(), "w2p_json_to_array");
        w2p_add_error_log("Parameters passed: " . print_r($json, true), "w2p_json_to_array");
        return [];
    }
}

function w2p_json_encode(array $array): string
{
    try {
        $formated_array = array_map(function ($value) {
            return is_numeric($value) ? strval($value) : $value;
        }, $array);

        $formated_string = json_encode($formated_array);

        return $formated_string ?? "[]";
    } catch (\Throwable $e) {
        w2p_add_error_log("Error: " . $e->getMessage(), "w2p_json_encode");
        w2p_add_error_log("Parameters passed: " . print_r($array, true), "w2p_json_encode");
        return "[]";
    }
}

function w2p_get_meta_key(string $category, string $suffix)
{
    return "W2P_$category" . "_$suffix";
}

function w2p_is_sync_running()
{
    $is_sync_running = get_option('w2p_sync_running', false);
    $last_heartbeat = get_option('w2p_sync_last_heartbeat', null);

    //si la sync semble avoir finialement été arrété pour X raison, on stop officielement la sync (4 heures)
    if ($is_sync_running && (!$last_heartbeat || time() - $last_heartbeat > 60 * 60 * 4)) {
        update_option('w2p_sync_running', false);
        wp_clear_scheduled_hook('w2p_cron_check_sync');

        $is_sync_running =  false;
    }

    return $is_sync_running;
}

function w2P_curl_request(string $url, string $method, array $data = array())
{
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $jsonData = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonData)
            ]);
        }

        $output = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            $response = [
                'success' => false,
                'error' => curl_error($ch),
                'status_code' => $http_status,
                'data' => null
            ];
        } else {
            $response = [
                'success' => true,
                'data' => w2p_json_to_array($output),
                'raw' => $output,
                'status_code' => $http_status
            ];
        }

        curl_close($ch);
        return $response;
    } catch (\Throwable $e) {
        w2p_add_error_log("Curl request failed: " . $e->getMessage(), "w2P_curl_request");
        w2p_add_error_log("URL: $url", "w2P_curl_request");
        w2p_add_error_log("Method: $method", "w2P_curl_request");

        // Retour structuré en cas d'erreur
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'status_code' => 500,
            'data' => null
        ];
    }
}



/**
 * Generates a cryptographically secure encryption key.
 * This key is 256 bits long (32 bytes) and is suitable for use with AES-256 or similar encryption algorithms.
 * @return string The generated encryption key in hexadecimal format.
 */
function w2p_generate_encryption_key()
{
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes(32)); // Preferred method for PHP 7+
    } elseif (function_exists('openssl_random_pseudo_bytes')) {
        $crypto_strong = false;
        $key = openssl_random_pseudo_bytes(32, $crypto_strong);

        if (!$crypto_strong) {
            wp_die('The encryption key could not be generated securely.');
        }

        return bin2hex($key);
    } else {
        wp_die('No secure random number generator is available.');
    }
}


function w2p_encrypt($data)
{
    try {
        if (!defined('W2P_ENCRYPTION_KEY')) {
            secret_key_init();
            if (!defined('W2P_ENCRYPTION_KEY')) {
                throw new Throwable('Encryption key not defined.');
            }
        }

        // Conversion de la clé hexadécimale en binaire
        $key = hex2bin(W2P_ENCRYPTION_KEY);
        if (strlen($key) !== 32) {
            throw new Throwable('Invalid encryption key length. Key must be 32 bytes for AES-256-CBC.');
        }

        $iv = openssl_random_pseudo_bytes(16); // Génère un IV de 16 octets
        $encrypted_data = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);

        if ($encrypted_data === false) {
            throw new Throwable('Encryption failed.');
        }

        return base64_encode($iv . $encrypted_data); // Combine IV et données chiffrées
    } catch (Throwable $e) {
        w2p_add_error_log('Encryption error: ' . $e->getMessage(), "w2p_encrypt");
        return $data;
    }
}

function w2p_decrypt($encrypted_data)
{
    try {
        if (!defined('W2P_ENCRYPTION_KEY')) {
            secret_key_init();
            if (!defined('W2P_ENCRYPTION_KEY')) {
                throw new Throwable('Encryption key not defined.');
            }
        }

        // Conversion de la clé hexadécimale en binaire
        $key = hex2bin(W2P_ENCRYPTION_KEY);
        if (strlen($key) !== 32) {
            throw new Throwable('Invalid encryption key length. Key must be 32 bytes for AES-256-CBC.');
        }

        $encrypted_data = base64_decode($encrypted_data);

        if (strlen($encrypted_data) < 16) {
            throw new Throwable('Encrypted data is too short to contain a valid IV.');
        }

        $iv = substr($encrypted_data, 0, 16); // Extract IV
        $encrypted_data = substr($encrypted_data, 16); // Extract encrypted data

        $decrypted_data = openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);

        if ($decrypted_data === false) {
            throw new Throwable('Decryption failed.');
        }

        return $decrypted_data;
    } catch (Throwable $e) {
        w2p_add_error_log('Decryption error: ' . $e->getMessage(), "w2p_decrypt");
        return $encrypted_data;
    }
}

function w2p_is_woocomerce_active()
{
    return class_exists('WC_Order');
}

function w2p_is_local_environment()
{
    return strpos($_SERVER['SERVER_NAME'], '.local') !== false;
}
