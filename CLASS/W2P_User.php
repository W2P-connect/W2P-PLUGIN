<?php

class W2P_User extends WP_User
{
    public function __construct(int $id = 0)
    {
        try {
            parent::__construct($id);
        } catch (\Throwable $e) {
            w2p_add_error_log("Error in W2P_User constructor: " . $e->getMessage(), "W2P_User->__construct()");
            w2p_add_error_log("Parameters passed: id = $id", "W2P_User->__construct()");
        }
    }

    /****************************************SET *************************************************************************/

    function update_meta_key(string $meta_key, $value)
    {
        try {
            update_user_meta($this->ID, $meta_key, $value);
        } catch (\Throwable $e) {
            w2p_add_error_log("Error in update_meta_key: " . $e->getMessage(), "W2P_User->update_meta_key()");
            w2p_add_error_log("Parameters passed: meta_key = $meta_key, value = " . print_r($value, true), "W2P_User->update_meta_key()");
        }
    }

    /****************************************GET *************************************************************************/

    public function get_lastName(): string
    {
        try {
            return $this->last_name;
        } catch (\Throwable $e) {
            w2p_add_error_log("Error in get_lastName: " . $e->getMessage(), "W2P_User->get_lastName()");
            w2p_add_error_log("Parameters passed: " . print_r($this, true), "W2P_User->get_lastName()");
            return ''; // Valeur par défaut en cas d'erreur
        }
    }

    public function get_firstName(): string
    {
        try {
            return $this->first_name;
        } catch (\Throwable $e) {
            w2p_add_error_log("Error in get_firstName: " . $e->getMessage(), "W2P_User->get_firstName()");
            w2p_add_error_log("Parameters passed: " . print_r($this, true), "W2P_User->get_firstName()");
            return ''; // Valeur par défaut en cas d'erreur
        }
    }

    public function get_company(): string
    {
        try {
            $billing_company = $this->get('billing_company');
            return $billing_company;
        } catch (\Throwable $e) {
            w2p_add_error_log("Error in get_company: " . $e->getMessage(), "W2P_User->get_company()");
            w2p_add_error_log("Parameters passed: " . print_r($this, true), "W2P_User->get_company()");
            return ''; // Valeur par défaut en cas d'erreur
        }
    }

    public function is_new_user(): bool
    {
        try {
            if ($this->get('w2p_new_user')) {
                return false;
            }

            $user_registered_date = $this->user_registered;
            $user_registered_datetime = new DateTime($user_registered_date);
            $current_date = new DateTime();
            $interval = $current_date->diff($user_registered_datetime);

            if ($interval->days == 0 && $interval->h == 0 && $interval->i <= 1) {
                return true;
            } else {
                $this->update_meta_key('w2p_new_user', 0);
                return false;
            }
        } catch (\Throwable $e) {
            w2p_add_error_log("Error in is_new_user: " . $e->getMessage(), "W2P_User->is_new_user()");
            w2p_add_error_log("Parameters passed: " . print_r($this, true), "W2P_User->is_new_user()");
            return false; // Valeur par défaut en cas d'erreur
        }
    }

    public function get_person_queries()
    {
        return W2P_Query::get_queries(
            true,
            [
                'category' => W2P_CATEGORY["person"],
                'source_id' => $this->ID
            ],
            1,
            -1
        )["data"];
    }
    
    public function get_organization_queries()
    {
        return W2P_Query::get_queries(
            true,
            [
                'category' => W2P_CATEGORY["organization"],
                'source_id' => $this->ID,
                'source' => W2P_HOOK_SOURCES["user"],
            ],
            1,
            -1
        )["data"];
    }
}
