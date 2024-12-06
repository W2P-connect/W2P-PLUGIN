<?php

trait W2P_Formater
{
    private function load_object_from_DB($id): void
    {
        global $wpdb;
        try {
            $object = $wpdb->get_row("SELECT * FROM $this->db_name WHERE id = $id;");
            if ($object) {
                foreach ($object as $key => $value) {
                    if (key_exists($key, $this->data) && $value) {
                        if (is_array($this->data[$key])) {
                            $this->data[$key] = w2p_json_to_array($value);
                        } else {
                            $this->data[$key] = is_numeric($value) ? floatval($value) : $value;
                        }
                    }
                }
                $this->new_instance = false;
            } else {
                $this->new_instance = true;
            }
        } catch (\Throwable $e) {
            w2p_add_error_log("Error loading object from DB: " . $e->getMessage(), 'load_object_from_DB');
        }
    }

    private function format_object_for_DB(): array
    {
        try {
            $formated_data = [];
            foreach ($this->data as $key => $value) {
                $formated_data[$key] = is_array($value)
                    ? w2p_json_encode($value)
                    : $value;
            }
            return $formated_data;
        } catch (\Throwable $e) {
            w2p_add_error_log("Error formatting object for DB: " . $e->getMessage(), 'format_object_for_DB');
            return [];
        }
    }

    public function save_to_database(): int
    {
        try {
            if ($this->is_savable()) {
                global $wpdb;
                if ($this->data["id"]) {
                    $result = $wpdb->update(
                        $this->db_name,
                        $this->format_object_for_DB(),
                        ['id' => $this->data["id"]]
                    );
                    return $result !== false ? $this->data["id"] : 0;
                } else {
                    $result = $wpdb->insert(
                        $this->db_name,
                        $this->format_object_for_DB()
                    );
                    return $result !== false ? $wpdb->insert_id : 0;
                }
            }
            return 0;
        } catch (\Throwable $e) {
            w2p_add_error_log("Error saving to DB: " . $e->getMessage(), 'save_to_database');
            return 0;
        }
    }
}
