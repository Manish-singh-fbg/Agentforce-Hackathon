<?php
header('Content-Type: application/json'); // Set JSON header
require_once '../config.php'; // Adjust path as needed
require_once 'auth.php';      // Include authentication check

// Authenticate the request
authenticate_api_request($db);

$method = $_SERVER['REQUEST_METHOD'];

// --- Handle GET Requests (Fetch Reviews/Responses) ---
if ($method === 'GET') {
    $product_id = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);
    $review_id = filter_input(INPUT_GET, 'review_id', FILTER_VALIDATE_INT);

    if ($product_id) {
        // Fetch all reviews (and their responses) for a product
        $reviews_data = [];
        $sql_reviews = "SELECT r.id, r.product_id, r.user_name, r.rating, r.review_text, r.created_at
                        FROM reviews r
                        WHERE r.product_id = ? ORDER BY r.created_at DESC";
        $result_reviews = execute_query($db, $sql_reviews, [$product_id], "i");

        if ($result_reviews) {
            while ($review = $result_reviews->fetch_assoc()) {
                // Fetch responses for this review
                $review['responses'] = [];
                $sql_responses = "SELECT id, responder_name, response_text, source, created_at
                                  FROM responses
                                  WHERE review_id = ? ORDER BY created_at ASC";
                $result_responses = execute_query($db, $sql_responses, [$review['id']], "i");
                 if ($result_responses) {
                    while ($response = $result_responses->fetch_assoc()) {
                         $review['responses'][] = $response;
                    }
                    $result_responses->free();
                 }
                 $reviews_data[] = $review;
            }
            $result_reviews->free();
            echo json_encode(['status' => 'success', 'data' => $reviews_data]);
        } else {
             http_response_code(500);
             echo json_encode(['status' => 'error', 'message' => 'Error fetching reviews.']);
        }

    } elseif ($review_id) {
        // Fetch a specific review (and its responses)
         $review_data = null;
         $sql_review = "SELECT r.id, r.product_id, r.user_name, r.rating, r.review_text, r.created_at
                        FROM reviews r
                        WHERE r.id = ?";
        $result_review = execute_query($db, $sql_review, [$review_id], "i");

        if ($result_review && $result_review->num_rows > 0) {
            $review_data = $result_review->fetch_assoc();
            $result_review->free();

            // Fetch responses
            $review_data['responses'] = [];
            $sql_responses = "SELECT id, responder_name, response_text, source, created_at
                              FROM responses
                              WHERE review_id = ? ORDER BY created_at ASC";
            $result_responses = execute_query($db, $sql_responses, [$review_id], "i");
             if ($result_responses) {
                while ($response = $result_responses->fetch_assoc()) {
                     $review_data['responses'][] = $response;
                }
                $result_responses->free();
             }
             echo json_encode(['status' => 'success', 'data' => $review_data]);

        } else {
             http_response_code(404);
             echo json_encode(['status' => 'error', 'message' => 'Review not found.']);
        }

    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing product_id or review_id parameter.']);
    }

// --- Handle POST Requests (Add a Response to a Review) ---
} elseif ($method === 'POST') {
    // Get data from request body (assuming JSON)
    $data = json_decode(file_get_contents('php://input'), true);

    // Basic Validation
    $review_id = filter_var($data['review_id'] ?? null, FILTER_VALIDATE_INT);
    $responder_name = trim(filter_var($data['responder_name'] ?? '', FILTER_SANITIZE_STRING));
    $response_text = trim(filter_var($data['response_text'] ?? '', FILTER_SANITIZE_STRING));

    if ($review_id && !empty($responder_name) && !empty($response_text)) {

        // Check if review exists
        $check_sql = "SELECT id FROM reviews WHERE id = ?";
        $check_result = execute_query($db, $check_sql, [$review_id], "i");

        if ($check_result && $check_result->num_rows > 0) {
            $check_result->free();

            // Insert the response
            $sql = "INSERT INTO responses (review_id, responder_name, response_text, source) VALUES (?, ?, ?, 'api')";
            $insert_result = execute_query($db, $sql, [$review_id, $responder_name, $response_text], "iss");

            if ($insert_result && $insert_result['affected_rows'] > 0) {
                http_response_code(201); // Created
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Response added successfully.',
                    'response_id' => $insert_result['insert_id']
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Failed to add response. Database error.']);
            }
        } else {
             http_response_code(404);
             echo json_encode(['status' => 'error', 'message' => 'Review not found.']);
        }

    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid or missing input data (review_id, responder_name, response_text required).']);
    }

} else {
    // Method Not Allowed
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed. Only GET and POST are supported.']);
}

$db->close();
?>