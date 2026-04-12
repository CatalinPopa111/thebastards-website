<?php
class p99_ajax {
  function __construct() {
    add_action('wp_ajax_p99_save_data', array($this, 'p99_save_data'));
    add_action('wp_ajax_nopriv_p99_save_data', array($this, 'p99_save_data'));
  }
  public function p99_save_data() {
    $data = $_POST['data'];
    $saveObj = [];
    $statuses = [];
    $names = [];
    $keys = [];
    $hasEmpty = [];

    // Fetch existing apikeys from the database
    $existingApiKeys = get_option('apikeys', []);

    // Convert existingApiKeys to a map for easier lookup
    $existingKeysMap = [];
    foreach ($existingApiKeys as $item) {
        if (isset($item['keyname'])) {
            $existingKeysMap[trim($item['keyname'])] = trim($item['keyvalue']);
        }
    }

    if (is_array($data)) {
        foreach ($data as $row) {
            if (empty($row['keyname'])) {
                $row['keystatus'] = ''; // unset status if one field empty
            }

            if (!isset($row['keystatus'])) {
                $row['keystatus'] = '';
            }

            // Clean up keyname from specific substrings
            $row['keyname'] = trim(str_replace(['â€“ Updates (365 Days)', 'â€“ Updates (Lifetime)', '- Updates (365 Days)', '- Updates (Lifetime)'], '', $row['keyname']));
            $trimmedKeyvalue = trim($row['keyvalue']);

            // Lookup existing keyvalue based on keyname
            $existingKeyvalue = $existingKeysMap[$row['keyname']] ?? '';

            // Check if the new keyvalue is valid and has changed compared to the existing one
            if ($this->isValidKey($trimmedKeyvalue) && $existingKeyvalue !== $trimmedKeyvalue) {
                $row['keyvalue'] = $trimmedKeyvalue;
            } else {
                // Retain the existing keyvalue from the database if the new keyvalue is invalid or hasn't changed
                $row['keyvalue'] = $existingKeyvalue;
            }

            $row['keystatus'] = trim($row['keystatus']);

            $saveObj[] = $row;
            $statuses[] = $row['keystatus'];
            $names[] = $row['keyname'];
            $keys[] = $row['keyvalue'];
        }
    }

    // Update the apikeys option with the modified data
    update_option('apikeys', $saveObj);
    echo json_encode([
        'status' => 200,
        'message' => 'Update keys saved.',
        'names' => $names,
        'keys' => $keys,
        'statuses' => $statuses
    ]);
    wp_die();
}

public function isValidKey($key) {
    if (empty($key)) {
        return false;
    }
    if (strlen($key) !== 32) {
        return false;
    }
    if (!preg_match('/^[a-z0-9]+$/', $key)) {
        return false;
    }
    return true;
}
}
new p99_ajax();