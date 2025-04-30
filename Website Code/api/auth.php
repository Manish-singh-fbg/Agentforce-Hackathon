<?php
// Included by API endpoint files

function authenticate_api_request($db) {
    // Get the API key from the request header
    $api_key = $_SERVER['HTTP_' . str_replace('-', '_', strtoupper(API_KEY_HEADER))] ?? null;

    if (!$api_key) {
        http_response_code(401); // Unauthorized
        echo json_encode(['status' => 'error', 'message' => 'API key missing.']);
        exit;
    }

    // Check the API key against the database
    $sql = "SELECT id FROM api_keys WHERE api_key = ? AND is_active = 1";
    $result = execute_query($db, $sql, [$api_key], "s");

    if ($result && $result->num_rows > 0) {
        $result->free();
        return true; // Authentication successful
    } else {
        http_response_code(403); // Forbidden
        echo json_encode(['status' => 'error', 'message' => 'Invalid or inactive API key.']);
        exit;
    }
}
?>