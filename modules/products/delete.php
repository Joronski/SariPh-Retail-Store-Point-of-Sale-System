<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/session.php';

    requireRole(['Administrator']);

    $database = new Database();
    $conn = $database->getConnection();

    $productId = $_GET['id'] ?? 0;

    try {
        // Get product name for audit log
        $stmt = $conn->prepare("SELECT product_name FROM products WHERE product_id = :id");
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch();
        
        if ($product) {
            // Soft delete - set as inactive instead of deleting
            $stmt = $conn->prepare("
                UPDATE products 
                SET is_active = 0
                WHERE product_id = :id
            ");
            $stmt->execute([':id' => $productId]);
            
            logAudit($conn, 'PRODUCT_DELETED', 'products', $productId, 
                "Product deactivated: " . $product['product_name']);
            
            header('Location: index.php?success=Product deactivated successfully');
        } else {
            header('Location: index.php?error=Product not found');
        }
    } catch(PDOException $e) {
        header('Location: index.php?error=Error deleting product');
    }

    exit();
?>