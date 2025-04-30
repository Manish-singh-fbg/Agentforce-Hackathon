<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic Validation & Sanitization
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $user_name = trim(filter_input(INPUT_POST, 'user_name', FILTER_SANITIZE_STRING));
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 5]]);
    $review_text = trim(filter_input(INPUT_POST, 'review_text', FILTER_SANITIZE_STRING));

    if ($product_id && !empty($user_name) && $rating && !empty($review_text)) {
        // Insert into database using prepared statement
        $sql = "INSERT INTO reviews (product_id, user_name, rating, review_text) VALUES (?, ?, ?, ?)";
        $result = execute_query($db, $sql, [$product_id, $user_name, $rating, $review_text], "isds");

        if ($result && $result['affected_rows'] > 0) {
            // Success - Redirect back to the product page
            header('Location: product.php?id=' . $product_id . '&status=review_success');
            exit;
        } else {
            // Database error
            header('Location: product.php?id=' . $product_id . '&status=review_error');
            exit;
        }
    } else {
        // Invalid input
        // Find which product id it was if possible, otherwise redirect to index
        if ($product_id) {
             header('Location: product.php?id=' . $product_id . '&status=review_invalid');
        } else {
             header('Location: index.php?status=review_invalid');
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