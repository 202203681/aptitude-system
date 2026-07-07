<?php include '../config/db.php'; ?>
<?php include '../includes/security.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Aptitude Testing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #003399 0%, #001a66 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 450px;
            margin: 80px auto;
        }
        .login-header {
            background: linear-gradient(135deg, #003399, #001a66);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-header i {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .login-body {
            background: white;
            padding: 40px;
        }
        .btn-login {
            background: linear-gradient(135deg, #003399, #001a66);
            border: none;
            padding: 12px;
            font-weight: 600;
            width: 100%;
        }
        .btn-login:hover {
            transform: translateY(-2px);
        }
        .alert {
            border-radius: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-brain"></i>
                <h2 class="mb-0">Smart Aptitude Testing System</h2>
                <p class="mb-0">Ministry of Education & Training</p>
                <p class="small">Kingdom of Eswatini</p>
            </div>
            <div class="login-body">
                <?php
                $security = new Security($conn);
                
                if ($_SERVER["REQUEST_METHOD"] == "POST") {
                    $email = $security->sanitize($_POST['email']);
                    $password = $_POST['password'];
                    
                    // Check rate limit
                    if (!$security->checkLoginAttempts($email)) {
                        echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Too many failed attempts. Please try again later.</div>";
                    } else {
                        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
                        $stmt->bind_param("s", $email);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($user = $result->fetch_assoc()) {
                            if ($password == $user['password']) {
                                $_SESSION['user_id'] = $user['id'];
                                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                                $_SESSION['user_email'] = $user['email'];
                                $_SESSION['role'] = $user['role'];
                                $_SESSION['last_activity'] = time();
                                
                                // Update last login
                                $conn->query("UPDATE users SET last_login = NOW() WHERE id = {$user['id']}");
                                
                                // Log successful login
                                logSystemAction($conn, $user['id'], 'login_success', ['email' => $email]);
                                
                                if ($user['role'] == 'admin') {
                                    header("Location: ../admin/dashboard.php");
                                } else {
                                    header("Location: ../user/dashboard.php");
                                }
                                exit();
                            } else {
                                $security->logFailedLogin($email);
                                echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Invalid password!</div>";
                            }
                        } else {
                            $security->logFailedLogin($email);
                            echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> User not found!</div>";
                        }
                    }
                }
                ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" class="form-control" name="email" required placeholder="student@school.sz">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-lock"></i> Password</label>
                        <input type="password" class="form-control" name="password" required placeholder="Enter your password">
                    </div>
                    <button type="submit" class="btn btn-login btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                    <div class="text-center mt-3">
                        <a href="register.php" class="text-decoration-none">Don't have an account? Register</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>