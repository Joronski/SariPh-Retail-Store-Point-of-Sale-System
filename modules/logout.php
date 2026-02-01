<?php
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/session.php';

    if (isLoggedIn()) {
        $database = new Database();
        $conn = $database->getConnection();
        
        logAudit($conn, 'LOGOUT', 'users', $_SESSION['user_id'], 'User logged out');
        
        session_destroy();
    }

    header('Location: login.php');
    exit();
?>