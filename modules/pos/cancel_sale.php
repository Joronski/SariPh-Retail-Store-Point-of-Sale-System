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
        
        // Log cancelled sale attempt
        $description = 'Sale cancelled before completion. Items: ' . count($input['items'] ?? []);
        logAudit($conn, 'SALE_CANCELLED', 'sales', null, $description);
        
        echo json_encode([
            'success' => true,
            'message' => 'Sale cancelled successfully'
        ]);
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
?>