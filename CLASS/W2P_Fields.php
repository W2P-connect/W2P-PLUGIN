<?php
class W2P_Fields
{
    private $field = array();

    public function __construct(array $field)
    {
        $this->field = $field;
    }

    public function get_field(): ?array
    {
        try {
            $field = null;
            if (isset($this->field["pipedriveFieldId"]) && is_int($this->field["pipedriveFieldId"])) {
                $parameters = w2p_get_parameters();
                $pipedrive_fields = $parameters["pipedrive"]["fields"];

                foreach ($pipedrive_fields as $field) {
                    if (isset($field["id"]) && $field["id"] === $this->field["pipedriveFieldId"]) {
                        $field = $field;
                        break;
                    }
                }
            }
            return $field;
        } catch (\Throwable $e) {
            w2p_add_error_log("Error in get_field for pipedriveFieldId: " . $this->field["pipedriveFieldId"] . " - " . $e->getMessage(), 'get_field');
            return null;
        }
    }

    public function get_data(?string $key = null)
    {
        try {
            return $key
                ? (isset($this->field[$key]) ? $this->field[$key] : null)
                : $this->field;
        } catch (\Throwable $e) {
            w2p_add_error_log("Error in get_data with key: $key - " . $e->getMessage(), 'get_data');
            return null;
        }
    }
}
