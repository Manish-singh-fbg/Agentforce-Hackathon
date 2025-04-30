<?php
require_once 'config.php'; // Include DB config and helpers

// Fetch all products
$products = [];
$result = execute_query($db, "SELECT id, name, price, image_url FROM products ORDER BY name ASC");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $result->free(); // Free result set
} else {
    // Handle error - maybe display a message
    echo "Error fetching products.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Commerce Site</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">
    <style>
      body {
        font-family: 'Inter', sans-serif;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }
      header {
        background-color: #007bff;
        color: white;
        padding: 20px;
        text-align: center;
      }
      header h1{
        font-family: 'Roboto Slab', serif;
        font-weight: 700;  /* Make the header bold */
        margin: 0;
        font-size: 2.5em; /* Increase font size for logo effect */
        letter-spacing: 2px; /* Add some letter spacing */
        text-shadow: 2px 2px 4px rgba(0,0,0,0.2); /* Add a subtle shadow */
      }
      footer {
        background-color: #f8f9fa;
        color: #343a40;
        padding: 20px;
        text-align: center;
        margin-top: 20px;
        border-top: 1px solid #ddd;
      }
      .product-list {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        margin: 20px;
      }
      .product-item {
        background-color: #ffffff;
        border: 1px solid #ddd;
        padding: 15px;
        margin: 10px;
        width: 280px;
        text-align: center;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
      }
      .product-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
      }
      .product-item img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
      }
      .product-item h2 {
        font-size: 1.2em;
        margin-top: 10px;
        color: #2c3e50;
        font-weight: 600;
        transition: color 0.3s ease;
      }
       .product-item h2:hover {
          color: #007bff;
        }
      .product-item p {
        font-size: 1em;
        color: #e74c3c;
        margin-bottom: 15px;
        font-weight: 700;
      }
      .product-item a {
        display: inline-block;
        padding: 10px 15px;
        background-color: #3498db;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        font-size: 1.1em;
        transition: background-color 0.3s ease;
        font-weight: 500;
      }
      .product-item a:hover {
        background-color: #217dbb;
        transform: scale(1.05);
      }
      @media (max-width: 768px) {
        .product-item {
          width: 90%;
        }
      }
      @media (max-width: 480px) {
        .product-item {
          width: 100%;
        }
        .product-list {
            margin: 10px;
        }
      }
    </style>
</head>
<body>
    <header>
        <h1>Innovate Electronics</h1>
    </header>
    <div class="product-list">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
                <div class="product-item">
                    <img src="<?php echo sanitize_output($product['image_url']); ?>" alt="<?php echo sanitize_output($product['name']); ?>" width="150">
                    <h2><?php echo sanitize_output($product['name']); ?></h2>
                    <p>Price: $<?php echo sanitize_output(number_format($product['price'], 2)); ?></p>
                    <a href="product.php?id=<?php echo $product['id']; ?>">View Details</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No products found.</p>
        <?php endif; ?>
    </div>
    <footer>
        <p>&copy; 2025 Innovate Electronics. All rights reserved.</p>
    </footer>
</body>
</html>
<?php $db->close(); // Close DB connection ?>
