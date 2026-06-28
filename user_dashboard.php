<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=Please login first");
    exit();
}

// Redirect admin to admin dashboard
if ($_SESSION['user_type'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit();
}

$user_stmt = $conn->prepare("SELECT username, email, first_name, surname, mobile, address, city, zip_code, country FROM users WHERE id = ?");
$user_stmt->execute([$_SESSION['user_id']]);
$current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);

if (!$current_user) {
    header("Location: logout.php");
    exit();
}

$username = $current_user['username'];
$display_name = trim(($current_user['first_name'] ?? '') . ' ' . ($current_user['surname'] ?? ''));
$email = $current_user['email'] ?? '';
$mobile = $current_user['mobile'] ?? '';
$address = $current_user['address'] ?? '';
$city = $current_user['city'] ?? '';
$zip_code = $current_user['zip_code'] ?? '';
$country = $current_user['country'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Moonchild</title>
    <link rel="stylesheet" href="style.css?v=2.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <h1 class="logo">Moonchild</h1>
        
        <div class="welcome-box">
            <div class="user-badge">CUSTOMER</div>
            <div class="welcome-icon user">
                <i class="fas fa-user"></i>
            </div>
            <h2 class="welcome-title">Hiii!</h2>
            <p class="welcome-message">Welcome, <?php echo htmlspecialchars($display_name ?: $username); ?>!</p>
            
            <div class="user-info">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($display_name ?: '-'); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                <p><strong>Mobile:</strong> <?php echo htmlspecialchars($mobile ?: '-'); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($address ?: '-'); ?></p>
                <p><strong>City:</strong> <?php echo htmlspecialchars($city ?: '-'); ?></p>
                <p><strong>ZIP Code:</strong> <?php echo htmlspecialchars($zip_code ?: '-'); ?></p>
                <p><strong>Country:</strong> <?php echo htmlspecialchars($country ?: '-'); ?></p>
                <p><strong>Role:</strong> Customer</p>
            </div>
            
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    <script src="script.js?v=2.1"></script>
</body>
</html>
