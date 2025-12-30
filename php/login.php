<?php
session_start();
require_once __DIR__ . '/../php/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
    $password = $_POST['password'] ?? '';

    $respond = function($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    };

    if (empty($email) || empty($password)) {
        $respond(['success' => false, 'message' => 'Please fill in all fields']);
    }

    $conn = getDBConnection();

    $stmt = $conn->prepare("SELECT id, full_name, email, username, password_hash FROM customers WHERE email = ? OR username = ? LIMIT 1");
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];

            $stmt->close();
            closeDBConnection($conn);
            $respond(['success' => true, 'message' => 'Login successful']);
        } else {
            $stmt->close();
            closeDBConnection($conn);
            $respond(['success' => false, 'message' => 'Invalid email/username or password']);
        }
    } else {
        if ($stmt) $stmt->close();
        closeDBConnection($conn);
        $respond(['success' => false, 'message' => 'Account not found']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Bereket Hotel</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .login-container { max-width: 400px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; font-family: sans-serif; }
        .error-msg { color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 10px; border: 1px solid #f5c6cb; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 10px; margin-top: 5px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; padding: 12px; background: #e67e22; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #d35400; }
        .reg-link { text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 style="text-align: center;">Customer Login</h2>
        
        <?php if ($error): ?>
            <p class="error-msg"><?php echo $error; ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="example@mail.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Enter your password">
            </div>
            <button type="submit">Login</button>
        </form>
        
        <p class="reg-link">Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</body>
</html>
