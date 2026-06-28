<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get products with discounts (discount > 0), ordered by highest discount
$prod_stmt = $conn->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.discount > 0 ORDER BY p.discount DESC");
$discount_products = $prod_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get cart count
$cart_stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
$cart_stmt->execute([$user_id]);
$cart_count = $cart_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Offers - Moonchild</title>
    <link rel="stylesheet" href="shop.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .offers-hero {
            background: linear-gradient(135deg, #ff6b6b 0%, #ffd700 50%, #00d4aa 100%);
            border-radius: 20px;
            padding: 30px 20px;
            text-align: center;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }
        
        .offers-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 60%);
            animation: shimmer 3s infinite linear;
        }
        
        @keyframes shimmer {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .offers-hero h2 {
            font-size: 2rem;
            color: #1a1f3a;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(255,255,255,0.3);
            position: relative;
            z-index: 1;
        }
        
        .offers-hero p {
            color: #1a1f3a;
            font-size: 1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .offers-hero .flash-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .discount-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: linear-gradient(135deg, #ff6b6b, #ff4757);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            z-index: 10;
            box-shadow: 0 3px 10px rgba(255, 107, 107, 0.4);
        }
        
        .original-price {
            text-decoration: line-through;
            color: #888;
            font-size: 0.85rem;
            margin-right: 8px;
        }
        
        .discounted-price {
            color: #00d4aa;
            font-weight: 700;
        }
        
        .savings-tag {
            display: inline-block;
            background: rgba(0, 212, 170, 0.2);
            color: #00d4aa;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
            margin-top: 5px;
        }
        
        .product-card {
            position: relative;
        }
        
        .no-offers {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }
        
        .no-offers i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            overflow-x: auto;
            padding-bottom: 10px;
        }
        
        .filter-tab {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 8px 16px;
            border-radius: 20px;
            color: #a0a0a0;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            text-decoration: none;
        }
        
        .filter-tab:hover, .filter-tab.active {
            background: linear-gradient(135deg, #ff6b6b, #ffd700);
            color: #1a1f3a;
            border-color: transparent;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="home.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
            <h1 class="logo">Moonchild</h1>
            <div class="header-icons">
                <a href="cart.php" class="icon-btn">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if($cart_count > 0): ?>
                    <span class="badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <!-- Offers Hero Banner -->
        <div class="offers-hero">
            <span class="flash-icon">⚡</span>
            <h2>Special Offers!</h2>
            <p>Grab amazing deals with up to 50% off on selected items</p>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="offers.php" class="filter-tab active">All Offers</a>
            <a href="offers.php?min=10&max=20" class="filter-tab">10-20% Off</a>
            <a href="offers.php?min=20&max=30" class="filter-tab">20-30% Off</a>
            <a href="offers.php?min=30&max=50" class="filter-tab">30-50% Off</a>
            <a href="offers.php?min=50" class="filter-tab">50%+ Off</a>
        </div>

        <!-- Discount Products -->
        <section class="section">
            <h3 class="section-title">
                <i class="fas fa-tags" style="color: #ff6b6b;"></i> 
                Hot Deals (<?php echo count($discount_products); ?> items)
            </h3>

            <div class="products-grid">
                <?php if(count($discount_products) > 0): ?>
                    <?php foreach($discount_products as $product): 
                        $discounted_price = $product['price'] - ($product['price'] * $product['discount'] / 100);
                        $savings = $product['price'] - $discounted_price;
                    ?>
                    <div class="product-card">
                        <span class="discount-badge">-<?php echo $product['discount']; ?>%</span>
                        <a href="product.php?id=<?php echo $product['id']; ?>">
                            <div class="product-image">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            </div>
                            <div class="product-info">
                                <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                <p class="product-price">
                                    <span class="original-price">₹<?php echo number_format($product['price'], 2); ?></span>
                                    <span class="discounted-price">₹<?php echo number_format($discounted_price, 2); ?></span>
                                </p>
                                <span class="savings-tag">Save ₹<?php echo number_format($savings, 2); ?></span>
                            </div>
                        </a>
                        <?php if($user_type === 'user'): ?>
                        <form action="cart_action.php" method="POST" class="product-actions">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="action" value="add">
                            <button type="submit" class="btn-add-cart">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-offers">
                        <i class="fas fa-tag"></i>
                        <h3>No Offers Available</h3>
                        <p>Check back soon for amazing deals!</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="home.php" class="nav-item">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="offers.php" class="nav-item active">
            <i class="fas fa-percent"></i>
            <span>Offers</span>
        </a>
        <a href="cart.php" class="nav-item center-btn">
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
    <script src="script.js?v=2.1"></script>
</body>
</html>
