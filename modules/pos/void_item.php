<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/session.php';

    requireLogin();
    requireRole(['Cashier', 'Supervisor', 'Administrator']);

    header('Content-Type: application/json');

    $database = new Database();
    $conn = $database->getConnection();

    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['sale_item_id'])) {
            throw new Exception('Invalid item ID');
        }
        
        $conn->beginTransaction();
        
        // Mark item as voided
        $stmt = $conn->prepare("
            UPDATE sale_items 
            SET is_voided = 1, voided_at = NOW()
            WHERE sale_item_id = :id
        ");
        $stmt->execute([':id' => $input['sale_item_id']]);
        
        // Log audit
        logAudit($conn, 'ITEM_VOIDED', 'sale_items', $input['sale_item_id'], 
            'Item voided from transaction');
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Item voided successfully'
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