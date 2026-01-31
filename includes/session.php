<?php
    session_start();

    // Check if user is logged in
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    // Check user role privileges
    function hasRole($role) {
        if (!isLoggedIn()) {
            return false;
        }
        
        if (is_array($role)) {
            return in_array($_SESSION['role'], $role);
        }
        
        return $_SESSION['role'] === $role;
    }

    // Require login
    function requireLogin() {
        if (!isLoggedIn()) {
            header('Location: /sariph-pos/modules/login.php');
            exit();
        }
    }

    // Require specific role
    function requireRole($role) {
        requireLogin();
        
        if (!hasRole($role)) {
            header('Location: /sariph-pos/modules/dashboard.php?error=unauthorized');
            exit();
        }
    }

    // Get current user info
    function getCurrentUser() {
        if (!isLoggedIn()) {
            return null;
        }
        
        return [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'role' => $_SESSION['role']
        ];
    }

    // Log audit trail
    function logAudit($conn, $action, $table_name, $record_id = null, $description = null) {
        if (!isLoggedIn()) {
            return false;
        }
        
        try {
            $stmt = $conn->prepare("
                INSERT INTO audit_log (user_id, action, table_name, record_id, description)
                VALUES (:user_id, :action, :table_name, :record_id, :description)
            ");
            
            $stmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':action' => $action,
                ':table_name' => $table_name,
                ':record_id' => $record_id,
                ':description' => $description
            ]);
            
            return true;
        } catch(PDOException $e) {
            return false;
        }
    }
?>