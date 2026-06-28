<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first");
    exit();
}

$user_type = $_SESSION['user_type'];
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Get cart count
$cart_stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
$cart_stmt->execute([$user_id]);
$cart_count = $cart_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

// Get wishlist count
$wish_stmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
$wish_stmt->execute([$user_id]);
$wishlist_count = $wish_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

// Get categories with their products
$cat_stmt = $conn->query("SELECT * FROM categories");
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get featured products
$prod_stmt = $conn->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.featured = 1 LIMIT 6");
$featured_products = $prod_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get products with discount for offers banner
$offer_stmt = $conn->query("SELECT COUNT(*) as count, MAX(discount) as max_discount FROM products WHERE discount > 0");
$offer_info = $offer_stmt->fetch(PDO::FETCH_ASSOC);

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_results = [];
if ($search) {
    $search_stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.name LIKE ? OR p.description LIKE ?");
    $search_term = "%$search%";
    $search_stmt->execute([$search_term, $search_term]);
    $search_results = $search_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get products by category
function getProductsByCategory($conn, $category_id, $limit = 4) {
    $limit = (int)$limit;
    $stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? LIMIT $limit");
    $stmt->execute([$category_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moonchild - Home</title>
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
                <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
            </form>
            <div class="header-icons">
                <a href="wishlist.php" class="icon-btn">
                    <i class="fas fa-heart"></i>
                    <?php if($wishlist_count > 0): ?>
                    <span class="badge"><?php echo $wishlist_count; ?></span>
                    <?php endif; ?>
                </a>
                <a href="cart.php" class="icon-btn">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if($cart_count > 0): ?>
                    <span class="badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Hero Banner -->
        <section class="hero-banner">
            <div class="hero-content">
                <h2>Experience Tomorrow,<br>Today.</h2>
                <p>Discover the latest innovations and exclusive arrivals.</p>
                <a href="offers.php" class="btn-primary">View Offers</a>
            </div>
            <div class="hero-image">
                <img src="https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=400" alt="Hero">
            </div>
        </section>

        <?php if($offer_info['count'] > 0): ?>
        <!-- Offers Banner -->
        <a href="offers.php" class="offers-banner" style="display: block; background: linear-gradient(135deg, #ff6b6b 0%, #ffd700 100%); border-radius: 15px; padding: 15px 20px; margin-bottom: 25px; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <span style="font-size: 2rem;">🔥</span>
                <div>
                    <h4 style="color: #1a1f3a; margin: 0; font-size: 1rem;">Special Offers!</h4>
                    <p style="color: #1a1f3a; margin: 0; font-size: 0.85rem; opacity: 0.8;">Up to <?php echo $offer_info['max_discount']; ?>% off on <?php echo $offer_info['count']; ?> items</p>
                </div>
            </div>
            <i class="fas fa-arrow-right" style="color: #1a1f3a;"></i>
        </a>
        <?php endif; ?>

        <!-- Categories Quick Links -->
        <section class="section">
            <h3 class="section-title">Browse Categories</h3>
            <div class="categories-grid">
                <?php foreach($categories as $cat): ?>
                <a href="#category-<?php echo $cat['id']; ?>" class="category-card" onclick="document.getElementById('category-<?php echo $cat['id']; ?>').scrollIntoView({behavior: 'smooth'}); return false;">
                    <div class="category-icon">
                        <i class="<?php echo $cat['icon']; ?>"></i>
                    </div>
                    <span><?php echo htmlspecialchars($cat['name']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if($search): ?>
        <!-- Search Results -->
        <section class="section">
            <h3 class="section-title">Search Results for "<?php echo htmlspecialchars($search); ?>"</h3>
            <div class="products-grid">
                <?php if(count($search_results) > 0): ?>
                    <?php foreach($search_results as $product): 
                        $discounted_price = $product['discount'] > 0 ? $product['price'] - ($product['price'] * $product['discount'] / 100) : $product['price'];
                    ?>
                    <div class="product-card">
                        <?php if($product['discount'] > 0): ?>
                        <span class="discount-badge" style="position: absolute; top: 10px; left: 10px; background: linear-gradient(135deg, #ff6b6b, #ff4757); color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: 700; z-index: 10;">-<?php echo $product['discount']; ?>%</span>
                        <?php endif; ?>
                        <a href="product.php?id=<?php echo $product['id']; ?>">
                            <div class="product-image">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            </div>
                            <div class="product-info">
                                <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                <?php if($product['discount'] > 0): ?>
                                <p class="product-price">
                                    <span style="text-decoration: line-through; color: #888; font-size: 0.85rem;">₹<?php echo number_format($product['price'], 2); ?></span>
                                    <span style="color: #00d4aa;">₹<?php echo number_format($discounted_price, 2); ?></span>
                                </p>
                                <?php else: ?>
                                <p class="product-price">₹<?php echo number_format($product['price'], 2); ?></p>
                                <?php endif; ?>
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
                    <p class="no-results">No products found for "<?php echo htmlspecialchars($search); ?>"
                <?php endif; ?>
            </div>
        </section>
        <?php else: ?>

        <!-- Featured Products -->
        <section class="section">
            <h3 class="section-title">⭐ Featured Products</h3>
            <div class="products-grid">
                <?php if(count($featured_products) > 0): ?>
                    <?php foreach($featured_products as $product): 
                        $discounted_price = $product['discount'] > 0 ? $product['price'] - ($product['price'] * $product['discount'] / 100) : $product['price'];
                    ?>
                    <div class="product-card">
                        <?php if($product['discount'] > 0): ?>
                        <span class="discount-badge" style="position: absolute; top: 10px; left: 10px; background: linear-gradient(135deg, #ff6b6b, #ff4757); color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: 700; z-index: 10;">-<?php echo $product['discount']; ?>%</span>
                        <?php endif; ?>
                        <a href="product.php?id=<?php echo $product['id']; ?>">
                            <div class="product-image">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            </div>
                            <div class="product-info">
                                <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                <?php if($product['discount'] > 0): ?>
                                <p class="product-price">
                                    <span style="text-decoration: line-through; color: #888; font-size: 0.85rem;">₹<?php echo number_format($product['price'], 2); ?></span>
                                    <span style="color: #00d4aa;">₹<?php echo number_format($discounted_price, 2); ?></span>
                                </p>
                                <?php else: ?>
                                <p class="product-price">₹<?php echo number_format($product['price'], 2); ?></p>
                                <?php endif; ?>
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
                    <p class="no-results">No featured products.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Category-wise Products -->
        <?php foreach($categories as $cat): 
            $cat_products = getProductsByCategory($conn, $cat['id'], 4);
            if(count($cat_products) > 0):
        ?>
        <section class="section" id="category-<?php echo $cat['id']; ?>">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 class="section-title" style="margin-bottom: 0;">
                    <i class="<?php echo $cat['icon']; ?>" style="margin-right: 10px; color: #00d4aa;"></i>
                    <?php echo htmlspecialchars($cat['name']); ?>
                </h3>
                <a href="home.php?category=<?php echo $cat['id']; ?>" style="color: #00d4aa; font-size: 0.85rem; text-decoration: none;">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="products-grid">
                <?php foreach($cat_products as $product): 
                    $discounted_price = $product['discount'] > 0 ? $product['price'] - ($product['price'] * $product['discount'] / 100) : $product['price'];
                ?>
                <div class="product-card">
                    <?php if($product['discount'] > 0): ?>
                    <span class="discount-badge" style="position: absolute; top: 10px; left: 10px; background: linear-gradient(135deg, #ff6b6b, #ff4757); color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: 700; z-index: 10;">-<?php echo $product['discount']; ?>%</span>
                    <?php endif; ?>
                    <a href="product.php?id=<?php echo $product['id']; ?>">
                        <div class="product-image">
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </div>
                        <div class="product-info">
                            <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                            <?php if($product['discount'] > 0): ?>
                            <p class="product-price">
                                <span style="text-decoration: line-through; color: #888; font-size: 0.85rem;">₹<?php echo number_format($product['price'], 2); ?></span>
                                <span style="color: #00d4aa;">₹<?php echo number_format($discounted_price, 2); ?></span>
                            </p>
                            <?php else: ?>
                            <p class="product-price">₹<?php echo number_format($product['price'], 2); ?></p>
                            <?php endif; ?>
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
            </div>
        </section>
        <?php endif; endforeach; ?>
        
        <?php endif; ?>
    </main>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="home.php" class="nav-item active">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="offers.php" class="nav-item">
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
