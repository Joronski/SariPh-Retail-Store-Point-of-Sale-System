<?php
    $page_title = 'Dashboard';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/header.php';

    requireLogin();

    $database = new Database();
    $conn = $database->getConnection();

    // Get statistics
    $stats = [
        'total_sales_today' => 0,
        'total_transactions_today' => 0,
        'total_products' => 0,
        'low_stock_products' => 0
    ];

    try {
        // Today's sales
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total
            FROM sales
            WHERE DATE(created_at) = CURDATE() AND status = 'Completed'
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        $stats['total_transactions_today'] = $result['count'];
        $stats['total_sales_today'] = $result['total'];
        
        // Total products
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
        $stmt->execute();
        $stats['total_products'] = $stmt->fetch()['count'];
        
        // Low stock (less than 10)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE stock_quantity < 10 AND is_active = 1");
        $stmt->execute();
        $stats['low_stock_products'] = $stmt->fetch()['count'];
        
    } catch(PDOException $e) {
        // Error handling
    }
?>

<div class="card">
    <div class="card-header">
        <h3>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h3>
    </div>
    
    <div class="dashboard-stats">
        <div class="stat-card">
            <h4>Today's Sales</h4>
            <div class="stat-value"><?php echo formatCurrency($stats['total_sales_today']); ?></div>
        </div>
        
        <div class="stat-card">
            <h4>Today's Transactions</h4>
            <div class="stat-value"><?php echo $stats['total_transactions_today']; ?></div>
        </div>
        
        <div class="stat-card">
            <h4>Total Products</h4>
            <div class="stat-value"><?php echo $stats['total_products']; ?></div>
        </div>
        
        <div class="stat-card">
            <h4>Low Stock Items</h4>
            <div class="stat-value"><?php echo $stats['low_stock_products']; ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Quick Actions</h3>
    </div>
    
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <?php if (hasRole(['Cashier', 'Supervisor', 'Administrator'])): ?>
            <a href="/sariph-pos/modules/pos/" class="btn btn-primary">New Transaction</a>
        <?php endif; ?>
        
        <?php if (hasRole(['Administrator'])): ?>
            <a href="/sariph-pos/modules/products/" class="btn btn-success">Manage Products</a>
            <a href="/sariph-pos/modules/users/" class="btn btn-warning">Manage Users</a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>