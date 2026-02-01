<?php
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../includes/session.php';

    // Redirect if already logged in
    if (isLoggedIn()) {
        header('Location: dashboard.php');
        exit();
    }

    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            $database = new Database();
            $conn = $database->getConnection();
            
            try {
                $stmt = $conn->prepare("
                    SELECT user_id, username, password, full_name, role, is_active
                    FROM users
                    WHERE username = :username
                ");
                $stmt->execute([':username' => $username]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    if ($user['is_active'] == 1) {
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['role'] = $user['role'];
                        
                        // Log login
                        logAudit($conn, 'LOGIN', 'users', $user['user_id'], 'User logged in');
                        
                        header('Location: dashboard.php');
                        exit();
                    } else {
                        $error = 'Your account has been deactivated. Please contact administrator.';
                    }
                } else {
                    $error = 'Invalid username or password.';
                }
            } catch(PDOException $e) {
                $error = 'Login failed. Please try again.';
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - <?php echo APP_NAME; ?></title>
        <link rel="stylesheet" href="/sariph-pos/assets/css/style.css">
    </head>
    <body>
        <div class="login-container">
            <div class="login-box">
                <h2><?php echo APP_NAME; ?></h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
                </form>
            </div>
        </div>
    </body>
</html>