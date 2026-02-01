<?php
    $page_title = 'Product Management';
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/header.php';

    requireRole(['Administrator']);

    $database = new Database();
    $conn = $database->getConnection();

    // Get all products
    $products = [];
    try {
        $stmt = $conn->prepare("
            SELECT * FROM products
            ORDER BY product_name
        ");
        $stmt->execute();
        $products = $stmt->fetchAll();
    } catch(PDOException $e) {
        $error = 'Error loading products';
    }

    $success = $_GET['success'] ?? '';
    $error = $_GET['error'] ?? '';
?>

<div class="card">
    <div class="card-header">
        <h3>Product Management</h3>
        <a href="add.php" class="btn btn-success">Add New Product</a>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <table class="table">
        <thead>
            <tr>
                <th>Barcode</th>
                <th>Product Name</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($products as $product): ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['barcode']); ?></td>
                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                    <td>₱<?php echo number_format($product['price'], 2); ?></td>
                    <td>
                        <?php echo $product['stock_quantity']; ?>
                        <?php if ($product['stock_quantity'] < 10): ?>
                            <span style="color: #e74c3c; font-weight: bold;">(Low Stock)</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($product['is_active']): ?>
                            <span style="color: #27ae60;">Active</span>
                        <?php else: ?>
                            <span style="color: #e74c3c;">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="edit.php?id=<?php echo $product['product_id']; ?>" class="btn btn-primary" style="padding: 5px 10px;">Edit</a>
                        <a href="delete.php?id=<?php echo $product['product_id']; ?>" class="btn btn-danger btn-delete" style="padding: 5px 10px;">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px; color: #7f8c8d;">
                        No products found. <a href="add.php">Add your first product</a>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>