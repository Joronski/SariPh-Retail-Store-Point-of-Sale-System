<?php
    $page_title = 'Add Product';
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/header.php';

    requireRole(['Administrator']);

    $database = new Database();
    $conn = $database->getConnection();

    $error = '';
    $success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $barcode = trim($_POST['barcode'] ?? '');
        $productName = trim($_POST['product_name'] ?? '');
        $price = $_POST['price'] ?? 0;
        $stockQuantity = $_POST['stock_quantity'] ?? 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($barcode) || empty($productName) || $price <= 0) {
            $error = 'Please fill in all required fields with valid values.';
        } else {
            try {
                // Check if barcode exists
                $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE barcode = :barcode");
                $stmt->execute([':barcode' => $barcode]);
                
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Barcode already exists.';
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO products (barcode, product_name, price, stock_quantity, is_active)
                        VALUES (:barcode, :product_name, :price, :stock_quantity, :is_active)
                    ");
                    
                    $stmt->execute([
                        ':barcode' => $barcode,
                        ':product_name' => $productName,
                        ':price' => $price,
                        ':stock_quantity' => $stockQuantity,
                        ':is_active' => $isActive
                    ]);
                    
                    $productId = $conn->lastInsertId();
                    
                    logAudit($conn, 'PRODUCT_ADDED', 'products', $productId, 
                        "Product added: $productName");
                    
                    header('Location: index.php?success=Product added successfully');
                    exit();
                }
            } catch(PDOException $e) {
                $error = 'Error adding product. Please try again.';
            }
        }
    }
?>

<div class="card">
    <div class="card-header">
        <h3>Add New Product</h3>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="barcode">Barcode *</label>
            <input type="text" id="barcode" name="barcode" class="form-control" required value="<?php echo htmlspecialchars($_POST['barcode'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="product_name">Product Name *</label>
            <input type="text" id="product_name" name="product_name" class="form-control" required value="<?php echo htmlspecialchars($_POST['product_name'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="price">Price *</label>
            <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="stock_quantity">Stock Quantity</label>
            <input type="number" id="stock_quantity" name="stock_quantity" class="form-control" min="0" value="<?php echo htmlspecialchars($_POST['stock_quantity'] ?? '0'); ?>">
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" <?php echo (isset($_POST['is_active']) || !isset($_POST['barcode'])) ? 'checked' : ''; ?>>
                Active
            </label>
        </div>
        
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-success">Add Product</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>