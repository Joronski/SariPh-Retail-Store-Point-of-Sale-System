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
        
        if (!isset($input['sale_id'])) {
            throw new Exception('Invalid sale ID');
        }
        
        // Get sale details
        $stmt = $conn->prepare("
            SELECT s.*, u.full_name as cashier_name
            FROM sales s
            JOIN users u ON s.cashier_id = u.user_id
            WHERE s.sale_id = :sale_id
        ");
        $stmt->execute([':sale_id' => $input['sale_id']]);
        $sale = $stmt->fetch();
        
        if (!$sale) {
            throw new Exception('Sale not found');
        }
        
        // Get sale items
        $stmt = $conn->prepare("
            SELECT product_name, price, quantity
            FROM sale_items
            WHERE sale_id = :sale_id AND is_voided = 0
        ");
        $stmt->execute([':sale_id' => $input['sale_id']]);
        $items = $stmt->fetchAll();
        
        // Log reprint
        $stmt = $conn->prepare("
            INSERT INTO receipt_reprints (sale_id, reprinted_by, reprint_reason)
            VALUES (:sale_id, :reprinted_by, 'Reprint requested')
        ");
        $stmt->execute([
            ':sale_id' => $input['sale_id'],
            ':reprinted_by' => $_SESSION['user_id']
        ]);
        
        logAudit($conn, 'RECEIPT_REPRINTED', 'sales', $input['sale_id'], 
            "Receipt reprinted for transaction {$sale['transaction_number']}");
        
        // Format items for response
        $formattedItems = [];
        foreach ($items as $item) {
            $formattedItems[] = [
                'name' => $item['product_name'],
                'price' => floatval($item['price']),
                'quantity' => intval($item['quantity'])
            ];
        }
        
        echo json_encode([
            'success' => true,
            'transaction_number' => $sale['transaction_number'],
            'items' => $formattedItems,
            'subtotal' => floatval($sale['subtotal']),
            'discount_type' => $sale['discount_type'],
            'discount_amount' => floatval($sale['discount_amount']),
            'total_amount' => floatval($sale['total_amount']),
            'payment_amount' => floatval($sale['payment_amount']),
            'change_amount' => floatval($sale['change_amount']),
            'store_name' => STORE_NAME,
            'store_address' => STORE_ADDRESS,
            'store_contact' => STORE_CONTACT,
            'store_tin' => STORE_TIN,
            'cashier_name' => $sale['cashier_name'],
            'date_time' => date('M d, Y h:i A', strtotime($sale['created_at'])),
            'is_reprint' => true
        ]);
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
?>