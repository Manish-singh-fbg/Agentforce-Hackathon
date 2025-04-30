<?php
header('Content-Type: application/json'); // Set JSON header
require_once '../config.php'; // Adjust path as needed
require_once 'auth.php';      // Include authentication check

// Authenticate the request
authenticate_api_request($db);

$method = $_SERVER['REQUEST_METHOD'];

// --- Handle GET Requests (Fetch Questions/Answers) ---
if ($method === 'GET') {
    $product_id = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);
    $question_id = filter_input(INPUT_GET, 'question_id', FILTER_VALIDATE_INT);

    if ($product_id) {
        // Fetch all questions (and their answers) for a product
        $questions_data = [];
        $sql_questions = "SELECT q.id, q.product_id, q.user_name, q.question_text, q.created_at
                          FROM questions q
                          WHERE q.product_id = ? ORDER BY q.created_at DESC";
        $result_questions = execute_query($db, $sql_questions, [$product_id], "i");

        if ($result_questions) {
            while ($question = $result_questions->fetch_assoc()) {
                // Fetch answers for this question
                $question['answers'] = [];
                $sql_answers = "SELECT id, responder_name, answer_text, source, created_at
                                FROM answers
                                WHERE question_id = ? ORDER BY created_at ASC";
                $result_answers = execute_query($db, $sql_answers, [$question['id']], "i");
                 if ($result_answers) {
                    while ($answer = $result_answers->fetch_assoc()) {
                         $question['answers'][] = $answer;
                    }
                    $result_answers->free();
                 }
                 $questions_data[] = $question;
            }
            $result_questions->free();
            echo json_encode(['status' => 'success', 'data' => $questions_data]);
        } else {
             http_response_code(500);
             echo json_encode(['status' => 'error', 'message' => 'Error fetching questions.']);
        }

    } elseif ($question_id) {
         // Fetch a specific question (and its answers)
         $question_data = null;
         $sql_question = "SELECT q.id, q.product_id, q.user_name, q.question_text, q.created_at
                          FROM questions q
                          WHERE q.id = ?";
        $result_question = execute_query($db, $sql_question, [$question_id], "i");

        if ($result_question && $result_question->num_rows > 0) {
            $question_data = $result_question->fetch_assoc();
            $result_question->free();

            // Fetch answers
            $question_data['answers'] = [];
            $sql_answers = "SELECT id, responder_name, answer_text, source, created_at
                            FROM answers
                            WHERE question_id = ? ORDER BY created_at ASC";
            $result_answers = execute_query($db, $sql_answers, [$question_id], "i");
             if ($result_answers) {
                while ($answer = $result_answers->fetch_assoc()) {
                     $question_data['answers'][] = $answer;
                }
                $result_answers->free();
             }
             echo json_encode(['status' => 'success', 'data' => $question_data]);

        } else {
             http_response_code(404);
             echo json_encode(['status' => 'error', 'message' => 'Question not found.']);
        }

    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Missing product_id or question_id parameter.']);
    }

// --- Handle POST Requests (Add an Answer to a Question) ---
} elseif ($method === 'POST') {
    // Get data from request body (assuming JSON)
    $data = json_decode(file_get_contents('php://input'), true);

    // Basic Validation
    $question_id = filter_var($data['question_id'] ?? null, FILTER_VALIDATE_INT);
    $responder_name = trim(filter_var($data['responder_name'] ?? '', FILTER_SANITIZE_STRING));
    $answer_text = trim(filter_var($data['answer_text'] ?? '', FILTER_SANITIZE_STRING));

    if ($question_id && !empty($responder_name) && !empty($answer_text)) {

        // Check if question exists
        $check_sql = "SELECT id FROM questions WHERE id = ?";
        $check_result = execute_query($db, $check_sql, [$question_id], "i");

        if ($check_result && $check_result->num_rows > 0) {
             $check_result->free();

            // Insert the answer
            $sql = "INSERT INTO answers (question_id, responder_name, answer_text, source) VALUES (?, ?, ?, 'api')";
            $insert_result = execute_query($db, $sql, [$question_id, $responder_name, $answer_text], "iss");

            if ($insert_result && $insert_result['affected_rows'] > 0) {
                http_response_code(201); // Created
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Answer added successfully.',
                    'answer_id' => $insert_result['insert_id']
                    ]);
            } else {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Failed to add answer. Database error.']);
            }
        } else {
             http_response_code(404);
             echo json_encode(['status' => 'error', 'message' => 'Question not found.']);
        }

    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid or missing input data (question_id, responder_name, answer_text required).']);
    }

} else {
    // Method Not Allowed
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed. Only GET and POST are supported.']);
}

$db->close();
?>