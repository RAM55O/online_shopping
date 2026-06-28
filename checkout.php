<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get cart items
$stmt = $conn->prepare("SELECT c.*, p.name, p.price, p.image, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($cart_items) == 0) {
    header("Location: cart.php?error=Your cart is empty");
    exit();
}

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = $subtotal > 100 ? 0 : 25;
$total = $subtotal + $shipping;

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $zip = trim($_POST['zip']);
    $country = trim($_POST['country']);
    $payment = $_POST['payment_method'];

    // Create order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, subtotal, shipping_cost, total_amount, shipping_address, shipping_city, shipping_zip, shipping_country, payment_method, estimated_delivery) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 5 DAY))");
    $stmt->execute([$user_id, $subtotal, $shipping, $total, $address, $city, $zip, $country, $payment]);
    $order_id = $conn->lastInsertId();

    // Add order items
    foreach ($cart_items as $item) {
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$order_id, $item['product_id'], $item['name'], $item['quantity'], $item['price']]);

        // Update stock
        $new_stock = $item['stock'] - $item['quantity'];
        if ($new_stock < 0) $new_stock = 0;
        $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
        $stmt->execute([$new_stock, $item['product_id']]);
    }

    // Clear cart
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);

    header("Location: order_success.php?order_id=" . $order_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Moonchild</title>
    <link rel="stylesheet" href="shop.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <a href="cart.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <h1 class="page-title">Checkout</h1>
    </div>

    <div class="checkout-container">
        <form action="checkout.php" method="POST" class="checkout-form">
            <!-- Shipping Address -->
            <div class="checkout-section">
                <h3><i class="fas fa-map-marker-alt"></i> Shipping Address</h3>
                <div class="form-group">
                    <label>Street Address *</label>
                    <input type="text" name="address" placeholder="123 Main Street" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>City *</label>
                        <input type="text" name="city" placeholder="New York" required>
                    </div>
                    <div class="form-group">
                        <label>ZIP Code *</label>
                        <input type="text" name="zip" placeholder="10001" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Country *</label>
                    <select name="country" required>
                        <option value="USA">United States</option>
                        <option value="UK">United Kingdom</option>
                        <option value="Canada">Canada</option>
                        <option value="Australia">Australia</option>
                        <option value="India">India</option>
                    </select>
                </div>
            </div>

            <!-- Payment Method -->
            <div class="checkout-section">
                <h3><i class="fas fa-credit-card"></i> Payment Method</h3>
                <div class="payment-options">
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="COD" checked>
                        <span class="payment-card">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Cash on Delivery</span>
                        </span>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="Card">
                        <span class="payment-card">
                            <i class="fas fa-credit-card"></i>
                            <span>Credit/Debit Card</span>
                        </span>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="UPI">
                        <span class="payment-card">
                            <i class="fas fa-mobile-alt"></i>
                            <span>UPI / Mobile Pay</span>
                        </span>
                    </label>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="checkout-section">
                <h3><i class="fas fa-receipt"></i> Order Summary</h3>
                <div class="checkout-items">
                    <?php foreach($cart_items as $item): ?>
                    <div class="checkout-item">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="">
                        <div class="checkout-item-info">
                            <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                            <p>Qty: <?php echo $item['quantity']; ?></p>
                        </div>
                        <span class="checkout-item-price">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="checkout-totals">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>₹<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>₹<?php echo number_format($shipping, 2); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>₹<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
            </div>

            <button type="submit" class="place-order-btn">
                <i class="fas fa-lock"></i> Place Order - ₹<?php echo number_format($total, 2); ?>
            </button>
        </form>
    </div>
    <script src="script.js?v=2.1"></script>
</body>
</html>
