<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic Validation & Sanitization
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $user_name = trim(filter_input(INPUT_POST, 'user_name', FILTER_SANITIZE_STRING));
    $question_text = trim(filter_input(INPUT_POST, 'question_text', FILTER_SANITIZE_STRING));

    if ($product_id && !empty($user_name) && !empty($question_text)) {
        // Insert into database using prepared statement
        $sql = "INSERT INTO questions (product_id, user_name, question_text) VALUES (?, ?, ?)";
        $result = execute_query($db, $sql, [$product_id, $user_name, $question_text], "iss");

        if ($result && $result['affected_rows'] > 0) {
            // Success - Redirect back to the product page
            header('Location: product.php?id=' . $product_id . '&status=question_success');
            exit;
        } else {
            // Database error
             header('Location: product.php?id=' . $product_id . '&status=question_error');
             exit;
        }
    } else {
        // Invalid input
        if ($product_id) {
             header('Location: product.php?id=' . $product_id . '&status=question_invalid');
        } else {
             header('Location: index.php?status=question_invalid');
        }
        exit;
    }
} else {
    // Not a POST request, redirect to homepage
    header('Location: index.php');
    exit;
}

$db->close();
?>