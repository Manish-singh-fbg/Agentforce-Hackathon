<?php
require_once 'config.php'; // Include DB config and helpers

// --- Get Product ID ---
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$product_id) {
    die("Invalid Product ID.");
}

// --- Fetch Product Details ---
$product = null;
$result = execute_query($db, "SELECT * FROM products WHERE id = ?", [$product_id], "i");
if ($result && $result->num_rows > 0) {
    $product = $result->fetch_assoc();
    $result->free();
} else {
    die("Product not found.");
}

// --- Fetch Reviews and their Responses ---
$reviews = [];
$sql_reviews = "SELECT r.id, r.user_name, r.rating, r.review_text, r.created_at
                  FROM reviews r
                  WHERE r.product_id = ?
                  ORDER BY r.created_at DESC";
$result_reviews = execute_query($db, $sql_reviews, [$product_id], "i");

if ($result_reviews) {
    while ($review = $result_reviews->fetch_assoc()) {
        // Fetch responses for this review
        $review['responses'] = [];
        $sql_responses = "SELECT responder_name, response_text, source, created_at
                                 FROM responses
                                 WHERE review_id = ?
                                 ORDER BY created_at ASC";
        $result_responses = execute_query($db, $sql_responses, [$review['id']], "i");
        if ($result_responses) {
            while ($response = $result_responses->fetch_assoc()) {
                $response['response_text'] = str_replace("&#39;", "'", $response['response_text']); //ADDED
                $review['responses'][] = $response;
            }
            $result_responses->free();
        }
        $review['review_text'] = str_replace("&#39;", "'", $review['review_text']); //ADDED
        $reviews[] = $review;
    }
    $result_reviews->free();
}


// --- Fetch Questions and their Answers ---
$questions = [];
$sql_questions = "SELECT q.id, q.user_name, q.question_text, q.created_at
                  FROM questions q
                  WHERE q.product_id = ?
                  ORDER BY q.created_at DESC";
$result_questions = execute_query($db, $sql_questions, [$product_id], "i");

if ($result_questions) {
    while ($question = $result_questions->fetch_assoc()) {
        // Fetch answers for this question
        $question['answers'] = [];
        $sql_answers = "SELECT responder_name, answer_text, source, created_at
                                 FROM answers
                                 WHERE question_id = ?
                                 ORDER BY created_at ASC";
        $result_answers = execute_query($db, $sql_answers, [$question['id']], "i");
        if ($result_answers) {
            while ($answer = $result_answers->fetch_assoc()) {
                $answer['answer_text'] = str_replace("&#39;", "'", $answer['answer_text']); //ADDED
                $question['answers'][] = $answer;
            }
            $result_answers->free();
        }
        $question['question_text'] = str_replace("&#39;", "'", $question['question_text']); //ADDED
        $questions[] = $question;
    }
    $result_questions->free();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize_output($product['name']); ?> - E-Commerce Site</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">
    <style>
      body {
        font-family: 'Inter', sans-serif;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        background-color: #f4f4f4;
        color: #333;
        line-height: 1.6;
      }
      header {
        background-color: #007bff;
        color: white;
        padding: 20px;
        text-align: center;
      }
      header h1{
        font-family: 'Roboto Slab', serif;
        font-weight: 700;
        margin: 0;
        font-size: 2.5em;
        letter-spacing: 2px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
      }
      footer {
        background-color: #f8f9fa;
        color: #343a40;
        padding: 20px;
        text-align: center;
        margin-top: 20px;
        border-top: 1px solid #ddd;
      }
      a {
        color: #007bff;
        text-decoration: none;
        transition: color 0.3s ease;
      }
      a:hover {
        color: #0056b3;
      }
      h1 {
        font-size: 2em;
        margin-top: 20px;
        margin-bottom: 10px;
        color: #2c3e50;
        font-family: 'Roboto Slab', serif;
        font-weight: 700;
      }
      h2, h3 {
        font-size: 1.5em;
        margin-top: 30px;
        margin-bottom: 15px;
        color: #2c3e50;
        font-family: 'Roboto Slab', serif;
        font-weight: 600;
      }
      img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      }
      p {
        margin-bottom: 15px;
        color: #555;
      }
      strong {
        color: #2c3e50;
      }
      small {
        color: #7f8c8d;
      }


      .products{
        margin: auto;
        width :50%;
      }
      .reviews-section, .questions-section {
        margin: auto;
        width :50%;
        padding: 20px;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      }
      .review-item, .question-item {
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
      }
      .review-item:last-child, .question-item:last-child {
        border-bottom: none;
      }
      .responses, .answers {
        margin-left: 20px;
        margin-top: 10px;
        padding-left: 15px;
        border-left: 2px solid #e0e0e0;
      }
      .response-item, .answer-item {
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f0f0f0;
      }
       .response-item:last-child,  .answer-item:last-child{
         border-bottom: none;
       }

      .star-rating {
        display: inline-block;
        margin-left: 5px;
      }
      .star-rating span {
        color: #ffc107;
        font-size: 1.2em;
      }
      form {
        margin-top: 20px;
        padding: 20px;
        background-color: #f9f9f9;
        border-radius: 8px;
        border: 1px solid #ddd;
      }
      form div {
        margin-bottom: 15px;
      }
      form label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #2c3e50;
      }
      input[type="text"], input[type="number"], textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        box-sizing: border-box;
        font-size: 1em;
        transition: border-color 0.3s ease;
      }
      input[type="text"]:focus, input[type="number"]:focus, textarea:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
      }
      textarea {
        resize: vertical;
      }
      button[type="submit"] {
        padding: 10px 20px;
        background-color: #28a745;
        color: white;
        border: none;
        border-radius: 5px;
        font-size: 1.1em;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.2s ease;
      }
      button[type="submit"]:hover {
        background-color: #218838;
        transform: translateY(-2px);
      }
      /* Popup Styles */
.modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgba(0,0,0,0.5);
}

.modal-content {
  background-color: #fefefe;
  margin: 10% auto;
  padding: 20px;
  border: 1px solid #888;
  width: 80%;
  border-radius: 8px;
  position: relative;
  box-shadow: 0 8px 20px rgba(0,0,0,0.3);
  animation: fadeIn 0.3s;
}
@keyframes fadeIn {
  from {opacity: 0; transform: translateY(-50px);}
  to {opacity: 1; transform: translateY(0);}
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid #ddd;
}
.modal-header h2{
    margin: 0;
    font-size: 1.8em;
}

.close-button {
  position: absolute;
  top: 10px;
  right: 10px;
  color: #aaa;
  font-size: 28px;
  font-weight: bold;
  cursor: pointer;
  transition: color 0.3s ease;
}

.close-button:hover,
.close-button:focus {
  color: #000;
  text-decoration: none;
}


#reviewModal .modal-content, #questionModal .modal-content{
    max-height: 80vh;
    overflow-y: auto;
}
    </style>
</head>
<body>
    <header>
        <h1>Innovate Electronics</h1>
    </header>
    <section class="products">
           <a href="index.php">&laquo; Back to Products</a>
            <h1><?php echo sanitize_output($product['name']); ?></h1>
            <img src="<?php echo sanitize_output($product['image_url']); ?>" alt="<?php echo sanitize_output($product['name']); ?>" width="300">
            <p><strong>Price: $<?php echo sanitize_output(number_format($product['price'], 2)); ?></strong></p>
            <p><?php echo nl2br(sanitize_output($product['description'])); ?></p> 
    </section>    

    <hr>

    <section class="reviews-section">
        <h2>Reviews</h2>
        <?php if (!empty($reviews)): ?>
            <?php foreach ($reviews as $review): ?>
                <div class="review-item">
                    <strong><?php echo sanitize_output($review['user_name']); ?></strong>
                    <span class="star-rating">
                         <?php for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $review['rating']) {
                                echo '<span>★</span>';
                            } else {
                                echo '☆';
                            }
                         }
                         ?>
                    </span>
                    -
                    <small><?php echo date('Y-m-d H:i', strtotime($review['created_at'])); ?></small>
                    <p><?php echo nl2br(sanitize_output($review['review_text'])); ?></p>

                    <?php if (!empty($review['responses'])): ?>
                        <div class="responses">
                            <h4>Responses:</h4>
                            <?php foreach ($review['responses'] as $response): ?>
                                <div class="response-item">
                                    <strong><?php echo sanitize_output($response['responder_name']); ?></strong>
                                    <?php if ($response['source'] == 'api'): ?>
                                        <small>(via API)</small>
                                    <?php endif; ?>
                                    - <small><?php echo date('Y-m-d H:i', strtotime($response['created_at'])); ?></small>
                                    <p><?php echo nl2br(sanitize_output($response['response_text'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div><hr>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No reviews yet.</p>
        <?php endif; ?>

        <button id="openReviewModal">Leave a Review</button>

<div id="reviewModal" class="modal">
  <div class="modal-content">
    <span class="close-button">&times;</span>
    <h2>Leave a Review</h2>
    <form action="add_review.php" method="post">
        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
        <div>
            <label for="user_name_review">Your Name:</label>
            <input type="text" id="user_name_review" name="user_name" required>
        </div>
        <div>
            <label for="rating">Rating (1-5):</label>
            <input type="number" id="rating" name="rating" min="1" max="5" required>
        </div>
        <div>
            <label for="review_text">Review:</label>
            <textarea id="review_text" name="review_text" rows="4" required></textarea>
        </div>
        <button type="submit">Submit Review</button>
    </form>
  </div>
</div>
    </section>

    <hr>

    <section class="questions-section">
        <h2>Questions & Answers</h2>
        <?php if (!empty($questions)): ?>
            <?php foreach ($questions as $question): ?>
                <div class="question-item">
                    <strong><?php echo sanitize_output($question['user_name']); ?> asked:</strong>
                    - <small><?php echo date('Y-m-d H:i', strtotime($question['created_at'])); ?></small>
                    <p><?php echo nl2br(sanitize_output($question['question_text'])); ?></p>

                    <?php if (!empty($question['answers'])): ?>
                        <div class="answers">
                            <h4>Answers:</h4>
                            <?php foreach ($question['answers'] as $answer): ?>
                                <div class="answer-item">
                                    <strong><?php echo sanitize_output($answer['responder_name']); ?></strong>
                                    <?php if ($answer['source'] == 'api'): ?>
                                        <small>(via API)</small>
                                    <?php endif; ?>
                                    - <small><?php echo date('Y-m-d H:i', strtotime($answer['created_at'])); ?></small>
                                    <p><?php echo nl2br(sanitize_output($answer['answer_text'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div><hr>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No questions yet.</p>
        <?php endif; ?>

        <button id="openQuestionModal">Ask a Question</button>

<div id="questionModal" class="modal">
  <div class="modal-content">
    <span class="close-button">&times;</span>
    <h2>Ask a Question</h2>
    <form action="add_question.php" method="post">
        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
        <div>
            <label for="user_name_question">Your Name:</label>
            <input type="text" id="user_name_question" name="user_name" required>
        </div>
        <div>
            <label for="question_text">Question:</label>
            <textarea id="question_text" name="question_text" rows="3" required></textarea>
        </div>
        <button type="submit">Submit Question</button>
    </form>
  </div>
</div>
    </section>
    <footer>
        <p>&copy; 2025 Innovate Electronics. All rights reserved.</p>
    </footer>
    <script>
    // Get the modal elements
    var reviewModal = document.getElementById("reviewModal");
    var questionModal = document.getElementById("questionModal");

    // Get the button that opens the modal
    var openReviewModalBtn = document.getElementById("openReviewModal");
    var openQuestionModalBtn = document.getElementById("openQuestionModal");

    // Get the <span> element that closes the modal
    var reviewCloseBtn = document.querySelector("#reviewModal .close-button");
    var questionCloseBtn = document.querySelector("#questionModal .close-button");

    // Function to open the review modal
    openReviewModalBtn.onclick = function() {
      reviewModal.style.display = "block";
    }

    // Function to open the question modal
    openQuestionModalBtn.onclick = function() {
      questionModal.style.display = "block";
    }

    // Function to close the review modal
    reviewCloseBtn.onclick = function() {
      reviewModal.style.display = "none";
    }

    // Function to close the question modal
    questionCloseBtn.onclick = function() {
      questionModal.style.display = "none";
    }

    // Close the modal when the user clicks outside of it
    window.onclick = function(event) {
      if (event.target == reviewModal) {
        reviewModal.style.display = "none";
      }
      if (event.target == questionModal) {
        questionModal.style.display = "none";
      }
    }
    </script>
</body>
</html>
<?php $db->close(); // Close DB connection ?>

