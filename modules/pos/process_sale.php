<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../includes/session.php';

    requireLogin();
    requireRole(['Cashier', 'Supervisor', 'Administrator']);

    header('Content-Type: application/json');

    $database = new Database();
    $conn = $database->getConnection();

    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || empty($input['items'])) {
            throw new Exception('Invalid sale data');
        }
        
        $conn->beginTransaction();
        
        // Generate transaction number
        $transactionNumber = 'TXN-' . date('Ymd') . '-' . sprintf('%06d', rand(1, 999999));
        
        // Check if transaction number exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM sales WHERE transaction_number = :tn");
        $stmt->execute([':tn' => $transactionNumber]);
        if ($stmt->fetchColumn() > 0) {
            $transactionNumber .= '-' . rand(1, 99);
        }
        
        // Insert sale record
        $stmt = $conn->prepare("
            INSERT INTO sales (
                transaction_number, cashier_id, subtotal, discount_type, 
                discount_amount, total_amount, payment_amount, change_amount, status
            ) VALUES (
                :transaction_number, :cashier_id, :subtotal, :discount_type,
                :discount_amount, :total_amount, :payment_amount, :change_amount, 'Completed'
            )
        ");
        
        $stmt->execute([
            ':transaction_number' => $transactionNumber,
            ':cashier_id' => $_SESSION['user_id'],
            ':subtotal' => $input['subtotal'],
            ':discount_type' => $input['discount_type'],
            ':discount_amount' => $input['discount_amount'],
            ':total_amount' => $input['total_amount'],
            ':payment_amount' => $input['payment_amount'],
            ':change_amount' => $input['change_amount']
        ]);
        
        $saleId = $conn->lastInsertId();
        
        // Insert sale items and update stock
        $stmt = $conn->prepare("
            INSERT INTO sale_items (sale_id, product_id, product_name, price, quantity, subtotal)
            VALUES (:sale_id, :product_id, :product_name, :price, :quantity, :subtotal)
        ");
        
        $updateStockStmt = $conn->prepare("
            UPDATE products 
            SET stock_quantity = stock_quantity - :quantity
            WHERE product_id = :product_id
        ");
        
        foreach ($input['items'] as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            
            $stmt->execute([
                ':sale_id' => $saleId,
                ':product_id' => $item['id'],
                ':product_name' => $item['name'],
                ':price' => $item['price'],
                ':quantity' => $item['quantity'],
                ':subtotal' => $subtotal
            ]);
            
            $updateStockStmt->execute([
                ':quantity' => $item['quantity'],
                ':product_id' => $item['id']
            ]);
        }
        
        // Log audit
        logAudit($conn, 'SALE_COMPLETED', 'sales', $saleId, 
            "Transaction: $transactionNumber, Total: " . $input['total_amount']);
        
        $conn->commit();
        
        // Prepare response
        echo json_encode([
            'success' => true,
            'sale_id' => $saleId,
            'transaction_number' => $transactionNumber,
            'items' => $input['items'],
            'subtotal' => $input['subtotal'],
            'discount_type' => $input['discount_type'],
            'discount_amount' => $input['discount_amount'],
            'total_amount' => $input['total_amount'],
            'payment_amount' => $input['payment_amount'],
            'change_amount' => $input['change_amount'],
            'store_name' => STORE_NAME,
            'store_address' => STORE_ADDRESS,
            'store_contact' => STORE_CONTACT,
            'store_tin' => STORE_TIN,
            'cashier_name' => $_SESSION['full_name'],
            'date_time' => date('M d, Y h:i A')
        ]);
        
    } catch(Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
?>