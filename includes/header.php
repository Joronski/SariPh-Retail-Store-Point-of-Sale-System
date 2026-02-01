<?php
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/session.php';
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
        
        <!-- External CSS -->
        <link rel="stylesheet" href="/sariph-pos/assets/css/style.css">
        
        <!-- jQuery CDN -->
        <script src="https://code.jquery.com/jquery-4.0.0.min.js"></script>
    </head>
    <body>
        <?php if (isLoggedIn()): ?>
            <nav class="navbar">
                <div class="nav-container">
                    <div class="nav-brand">
                        <h2><?php echo APP_NAME; ?></h2>
                    </div>
                    <ul class="nav-menu">
                        <li><a href="/sariph-pos/modules/dashboard.php">Dashboard</a></li>
                        
                        <?php if (hasRole(['Cashier', 'Supervisor', 'Administrator'])): ?>
                            <li><a href="/sariph-pos/modules/pos/">POS</a></li>
                        <?php endif; ?>
                        
                        <?php if (hasRole(['Administrator'])): ?>
                            <li><a href="/sariph-pos/modules/products/">Products</a></li>
                            <li><a href="/sariph-pos/modules/users/">Users</a></li>
                        <?php endif; ?>
                        
                        <li class="user-info">
                            <span><?php echo htmlspecialchars($_SESSION['full_name']); ?> (<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>
                            <a href="/sariph-pos/modules/logout.php" class="btn-logout">Logout</a>
                        </li>
                    </ul>
                </div>
            </nav>
        <?php endif; ?>
        
        <div class="container">