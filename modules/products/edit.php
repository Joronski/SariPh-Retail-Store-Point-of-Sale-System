<?php
    $page_title = 'Edit Product';
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/header.php';

    requireRole(['Administrator']);

    $database = new Database();
    $conn = $database->getConnection();

    $productId = $_GET['id'] ?? 0;
    $error = '';

    // Get product details
    $product = null;
    try {
        $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = :id");
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch();
        
        if (!$product) {
            header('Location: index.php?error=Product not found');
            exit();
        }
    } catch(PDOException $e) {
        header('Location: index.php?error=Error loading product');
        exit();
    }

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
                // Check if barcode exists for other products
                $stmt = $conn->prepare("
                    SELECT COUNT(*) FROM products 
                    WHERE barcode = :barcode AND product_id != :id
                ");
                $stmt->execute([':barcode' => $barcode, ':id' => $productId]);
                
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Barcode already exists for another product.';
                } else {
                    $stmt = $conn->prepare("
                        UPDATE products 
                        SET barcode = :barcode,
                            product_name = :product_name,
                            price = :price,
                            stock_quantity = :stock_quantity,
                            is_active = :is_active
                        WHERE product_id = :id
                    ");
                    
                    $stmt->execute([
                        ':barcode' => $barcode,
                        ':product_name' => $productName,
                        ':price' => $price,
                        ':stock_quantity' => $stockQuantity,
                        ':is_active' => $isActive,
                        ':id' => $productId
                    ]);
                    
                    logAudit($conn, 'PRODUCT_UPDATED', 'products', $productId, 
                        "Product updated: $productName");
                    
                    header('Location: index.php?success=Product updated successfully');
                    exit();
                }
            } catch(PDOException $e) {
                $error = 'Error updating product. Please try again.';
            }
        }
        
        // Update $product array with POST data
        $product['barcode'] = $barcode;
        $product['product_name'] = $productName;
        $product['price'] = $price;
        $product['stock_quantity'] = $stockQuantity;
        $product['is_active'] = $isActive;
    }
?>

<div class="card">
    <div class="card-header">
        <h3>Edit Product</h3>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="barcode">Barcode *</label>
            <input type="text" id="barcode" name="barcode" class="form-control" required value="<?php echo htmlspecialchars($product['barcode']); ?>">
        </div>
        
        <div class="form-group">
            <label for="product_name">Product Name *</label>
            <input type="text" id="product_name" name="product_name" class="form-control" required value="<?php echo htmlspecialchars($product['product_name']); ?>">
        </div>
        
        <div class="form-group">
            <label for="price">Price *</label>
            <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required value="<?php echo htmlspecialchars($product['price']); ?>">
        </div>
        
        <div class="form-group">
            <label for="stock_quantity">Stock Quantity</label>
            <input type="number" id="stock_quantity" name="stock_quantity" class="form-control" min="0" value="<?php echo htmlspecialchars($product['stock_quantity']); ?>">
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" <?php echo $product['is_active'] ? 'checked' : ''; ?>>
                Active
            </label>
        </div>
        
        <div style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-success">Update Product</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>