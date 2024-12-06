<?php
trait W2P_SetterTrait
{
    public function setter(string $key, $value): void
    {
        try {
            key_exists($key, $this->data) && $this->data[$key] = $value;
        } catch (\Throwable $e) {
            w2p_add_error_log("Error in setter for key: $key, value: " . print_r($value, true) . " - " . $e->getMessage(), 'setter');
        }
    }

    public function getter(string $key)
    {
        try {
            return key_exists($key, $this->data)
                ? $this->data[$key]
                : null;
        } catch (\Throwable $e) {
            w2p_add_error_log("Error in getter for key: $key - " . $e->getMessage(), 'getter');
            return null;
        }
    }

    public function set_from_array(array $params)
    {
        try {
            foreach ($params as $key => $value) {
                key_exists($key, $this->data) && $this->setter($key, $value);
            }
        } catch (\Throwable $e) {
            w2p_add_error_log("Error in set_from_array with params: " . print_r($params, true) . " - " . $e->getMessage(), 'set_from_array');
        }
    }

    public function get_id(): int
    {
        try {
            if ($this->data["id"]) {
                return $this->data["id"];
            } else {
                if ($this->is_savable()) {
                    $id = $this->save_to_database();
                    return $id;
                } else {
                    return 0;
                }
            }
        } catch (\Throwable $e) {
            w2p_add_error_log("Error in get_id - " . $e->getMessage(), 'get_id');
            return 0;
        }
    }
}
