<?php include '../config/db.php'; ?>
<?php include '../includes/security.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Smart Aptitude Testing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #003399 0%, #001a66 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .register-card {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            margin: 40px auto;
        }
        .register-header {
            background: linear-gradient(135deg, #003399, #001a66);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .register-body {
            background: white;
            padding: 40px;
        }
        .btn-register {
            background: linear-gradient(135deg, #003399, #001a66);
            border: none;
            padding: 12px;
            font-weight: 600;
            width: 100%;
        }
        .input-icon {
            position: relative;
        }
        .input-icon i {
            position: absolute;
            left: 15px;
            top: 12px;
            color: #003399;
        }
        .input-icon input, .input-icon select {
            padding-left: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-card">
            <div class="register-header">
                <i class="fas fa-user-graduate fa-3x mb-3"></i>
                <h2 class="mb-0">Student Registration</h2>
                <p class="mb-0">Join the Smart Aptitude Testing System</p>
            </div>
            <div class="register-body">
                <?php
                $security = new Security($conn);
                
                if ($_SERVER["REQUEST_METHOD"] == "POST") {
                    $first_name = $security->sanitize($_POST['first_name']);
                    $last_name = $security->sanitize($_POST['last_name']);
                    $student_id = $security->sanitize($_POST['student_id']);
                    $school = $security->sanitize($_POST['school']);
                    $region = $security->sanitize($_POST['region']);
                    $pobox = $security->sanitize($_POST['pobox']);
                    $year = $security->sanitize($_POST['year']);
                    $email = $security->sanitize($_POST['email']);
                    $password = $_POST['password'];
                    
                    // Validate password strength
                    $pass_errors = $security->validatePassword($password);
                    if (!empty($pass_errors)) {
                        echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> " . implode('<br>', $pass_errors) . "</div>";
                    } elseif (!$security->validateEmail($email)) {
                        echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Invalid email format!</div>";
                    } elseif ($first_name && $last_name && $student_id && $email && $password) {
                        $check = $conn->prepare("SELECT id FROM users WHERE email = ? OR student_id = ?");
                        $check->bind_param("ss", $email, $student_id);
                        $check->execute();
                        $check->store_result();
                        
                        if ($check->num_rows > 0) {
                            echo "<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Email or Student ID already exists!</div>";
                        } else {
                            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, student_id, school, region, pobox, year, email, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->bind_param("sssssssss", $first_name, $last_name, $student_id, $school, $region, $pobox, $year, $email, $password);
                            
                            if ($stmt->execute()) {
                                echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Registration successful! Please login.</div>";
                                echo "<meta http-equiv='refresh' content='2;url=login.php'>";
                            } else {
                                echo "<div class='alert alert-danger'>Registration failed: " . $conn->error . "</div>";
                            }
                        }
                    } else {
                        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> Please fill all required fields!</div>";
                    }
                }
                ?>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="input-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" class="form-control" name="first_name" placeholder="First Name *" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="input-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" class="form-control" name="last_name" placeholder="Last Name *" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="input-icon">
                                <i class="fas fa-id-card"></i>
                                <input type="text" class="form-control" name="student_id" placeholder="Student ID *" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="input-icon">
                                <i class="fas fa-school"></i>
                                <input type="text" class="form-control" name="school" placeholder="School">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="input-icon">
                                <i class="fas fa-map-marker-alt"></i>
                                <input type="text" class="form-control" name="region" placeholder="Region">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="input-icon">
                                <i class="fas fa-envelope"></i>
                                <input type="text" class="form-control" name="pobox" placeholder="P.O. Box">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="input-icon">
                                <i class="fas fa-calendar"></i>
                                <select class="form-control" name="year">
                                    <option>2028</option><option>2027</option><option>2026</option><option>2025</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="input-icon">
                                <i class="fas fa-envelope"></i>
                                <input type="email" class="form-control" name="email" placeholder="Email *" required>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="input-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" class="form-control" name="password" placeholder="Password (min 8 chars, uppercase, number) *" required>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-register btn-primary">
                                <i class="fas fa-user-plus"></i> Register
                            </button>
                        </div>
                        <div class="col-md-12 mt-3 text-center">
                            <a href="login.php" class="text-decoration-none">Already have an account? Login</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>