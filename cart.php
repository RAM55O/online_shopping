<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get cart items
$stmt = $conn->prepare("SELECT c.*, p.name, p.price, p.image FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = $subtotal > 100 ? 0 : 25;
$total = $subtotal + $shipping;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Moonchild</title>
    <link rel="stylesheet" href="shop.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <h1 class="logo">Moonchild</h1>
            <form class="search-bar" action="home.php" method="GET">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search products...">
            </form>
        </div>
    </header>

    <div class="cart-page-container">
        <h2 class="cart-page-title">Your Shopping Cart</h2>

        <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>

        <?php if(count($cart_items) > 0): ?>
            <div class="cart-items-list">
                <?php foreach($cart_items as $item): ?>
                <div class="cart-item-card">
                    <div class="cart-item-img">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="">
                    </div>
                    <div class="cart-item-info">
                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                        <p class="cart-item-price">₹<?php echo number_format($item['price'], 2); ?></p>
                        <form action="cart_action.php" method="POST" class="cart-qty-form">
                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                            <input type="hidden" name="action" value="update">
                            <div class="cart-qty-controls">
                                <button type="button" class="cart-qty-btn" onclick="updateCartQty(this, -1)">−</button>
                                <input type="number" name="quantity" class="cart-qty-input" value="<?php echo $item['quantity']; ?>" min="1">
                                <button type="button" class="cart-qty-btn" onclick="updateCartQty(this, 1)">+</button>
                            </div>
                        </form>
                    </div>
                    <div class="cart-item-actions-col">
                        <form action="cart_action.php" method="POST">
                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                            <input type="hidden" name="action" value="remove">
                            <button type="submit" class="cart-action-btn delete"><i class="fas fa-trash"></i></button>
                        </form>
                        <form action="wishlist_action.php" method="POST">
                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                            <input type="hidden" name="action" value="add">
                            <button type="submit" class="cart-action-btn wishlist"><i class="fas fa-heart"></i></button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="order-summary-card">
                <h3>Order Summary</h3>
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
                <form action="checkout.php" method="POST">
                    <button type="submit" class="checkout-btn">Proceed to Checkout</button>
                </form>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your cart is empty</h3>
                <p>Browse our products and add items to your cart!</p>
                <a href="home.php" class="btn-primary" style="display: inline-block; margin-top: 20px;">Continue Shopping</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="home.php" class="nav-item">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="offers.php" class="nav-item">
            <i class="fas fa-percent"></i>
            <span>Offers</span>
        </a>
        <a href="cart.php" class="nav-item center-btn active">
            <div class="nav-icon-wrapper">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <span>Cart</span>
        </a>
        <a href="wishlist.php" class="nav-item">
            <i class="fas fa-heart"></i>
            <span>Wishlist</span>
        </a>
        <a href="profile.php" class="nav-item">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </nav>

    <script>
        function updateCartQty(btn, delta) {
            const form = btn.closest('form');
            const input = form.querySelector('input[name="quantity"]');
            let val = parseInt(input.value) + delta;
            if (val < 1) val = 1;
            input.value = val;
            form.submit();
        }
    </script>
    <script src="script.js?v=2.1"></script>
</body>
</html>
