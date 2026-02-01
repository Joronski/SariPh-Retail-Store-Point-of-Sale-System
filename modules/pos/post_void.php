<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/session.php';

    requireLogin();

    header('Content-Type: application/json');

    $database = new Database();
    $conn = $database->getConnection();

    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['transaction_number']) || !isset($input['void_reason']) ||
            !isset($input['supervisor_username']) || !isset($input['supervisor_password'])) {
            throw new Exception('Missing required fields');
        }
        
        // Verify supervisor credentials
        $stmt = $conn->prepare("
            SELECT user_id, password, role
            FROM users
            WHERE username = :username AND is_active = 1
        ");
        $stmt->execute([':username' => $input['supervisor_username']]);
        $supervisor = $stmt->fetch();
        
        if (!$supervisor || !password_verify($input['supervisor_password'], $supervisor['password'])) {
            throw new Exception('Invalid supervisor credentials');
        }
        
        if ($supervisor['role'] !== 'Supervisor' && $supervisor['role'] !== 'Administrator') {
            throw new Exception('User is not authorized to approve voids');
        }
        
        $conn->beginTransaction();
        
        // Get sale details
        $stmt = $conn->prepare("
            SELECT sale_id, status
            FROM sales
            WHERE transaction_number = :tn
        ");
        $stmt->execute([':tn' => $input['transaction_number']]);
        $sale = $stmt->fetch();
        
        if (!$sale) {
            throw new Exception('Transaction not found');
        }
        
        if ($sale['status'] !== 'Completed') {
            throw new Exception('Transaction is already voided or cancelled');
        }
        
        // Get sale items to restore stock
        $stmt = $conn->prepare("
            SELECT product_id, quantity
            FROM sale_items
            WHERE sale_id = :sale_id
        ");
        $stmt->execute([':sale_id' => $sale['sale_id']]);
        $items = $stmt->fetchAll();
        
        // Restore stock
        $updateStockStmt = $conn->prepare("
            UPDATE products 
            SET stock_quantity = stock_quantity + :quantity
            WHERE product_id = :product_id
        ");
        
        foreach ($items as $item) {
            $updateStockStmt->execute([
                ':quantity' => $item['quantity'],
                ':product_id' => $item['product_id']
            ]);
        }
        
        // Update sale status
        $stmt = $conn->prepare("
            UPDATE sales 
            SET status = 'Voided',
                voided_by = :voided_by,
                void_reason = :void_reason,
                voided_at = NOW()
            WHERE sale_id = :sale_id
        ");
        
        $stmt->execute([
            ':voided_by' => $supervisor['user_id'],
            ':void_reason' => $input['void_reason'],
            ':sale_id' => $sale['sale_id']
        ]);
        
        // Log audit
        logAudit($conn, 'SALE_POST_VOIDED', 'sales', $sale['sale_id'], 
            "Transaction {$input['transaction_number']} voided by supervisor. Reason: {$input['void_reason']}");
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Transaction voided successfully'
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