<?php
// --- Database Configuration ---
define('DB_SERVER', 'localhost'); // Or your DB host
define('DB_USERNAME', 'u427190580_gs_admin');    // Your DB username
define('DB_PASSWORD', 'Riya@49mani');        // Your DB password
define('DB_NAME', 'u427190580_my_ecommerce');

// --- Establish Database Connection (MySQLi) ---
$db = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($db->connect_error) {
    // In a real app, log this error and show a user-friendly message
    die("Database Connection Failed: " . $db->connect_error);
}

// Set character set to UTF8MB4 for broader character support
$db->set_charset("utf8mb4");


// --- Helper Functions ---

// Basic function to prevent XSS attacks
function sanitize_output($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

// Function to execute prepared statements (prevents SQL injection)
function execute_query($db, $sql, $params = [], $types = "") {
    $stmt = $db->prepare($sql);
    if ($stmt === false) {
        // Handle error - log it, return false, etc.
        error_log("Prepare failed: (" . $db->errno . ") " . $db->error . " SQL: " . $sql);
        return false;
    }

    if ($params && $types) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        // Handle error
        error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
        $stmt->close();
        return false;
    }

    // For SELECT queries, return the result object
    // For INSERT/UPDATE/DELETE, return the statement object or true
    $result = $stmt->get_result();
    if ($result) {
        return $result; // Return mysqli_result for SELECT
    } else {
         // Return the statement for insert/update/delete info (affected_rows, insert_id)
         // or simply true if only success status is needed
        $insert_id = $stmt->insert_id;
        $affected_rows = $stmt->affected_rows;
        $stmt->close();
        return ['affected_rows' => $affected_rows, 'insert_id' => $insert_id];
    }
}

// --- API Key Configuration (for simplicity, keep it here) ---
define('API_KEY_HEADER', 'X-API-KEY'); // Standard header name for API keys

?>