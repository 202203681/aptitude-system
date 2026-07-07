<?php
// Check if user is logged in to show different menu items
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'user';
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        .navbar-custom {
            background: linear-gradient(135deg, var(--primary, #003399), var(--primary-dark, #001a66));
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white !important;
        }
        
        .navbar-brand i {
            color: var(--secondary, #CE9F32);
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            transition: all 0.3s;
            margin: 0 5px;
        }
        
        .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }
        
        .dropdown-menu {
            background: var(--card-bg, white);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border: none;
            margin-top: 10px;
        }
        
        .dropdown-item {
            transition: all 0.3s;
            padding: 10px 20px;
        }
        
        .dropdown-item:hover {
            background: var(--primary-light, #e3f2fd);
            transform: translateX(5px);
        }
        
        .logout-btn {
            color: #dc3545 !important;
        }
        
        .logout-btn:hover {
            background: #dc3545 !important;
            color: white !important;
        }
        
        .user-avatar-small {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 8px;
        }
        
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?= $is_logged_in ? ($user_role == 'admin' ? '../admin/dashboard.php' : '../user/dashboard.php') : '../index.php' ?>">
            <i class="fas fa-brain"></i> SATS
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if($is_logged_in): ?>
                    <!-- Dashboard Link -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $user_role == 'admin' ? '../admin/dashboard.php' : '../user/dashboard.php' ?>">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    
                    <!-- Tests Link (Students only) -->
                    <?php if($user_role == 'user'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../user/dashboard.php#tests">
                            <i class="fas fa-tasks"></i> Take Test
                        </a>
                    </li>
                    
                    <!-- Certificates Link -->
                    <li class="nav-item">
                        <a class="nav-link" href="../user/certificates.php">
                            <i class="fas fa-certificate"></i> Certificates
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Admin Links -->
                    <?php if($user_role == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../admin/manage_questions.php">
                            <i class="fas fa-database"></i> Questions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../admin/analytics.php">
                            <i class="fas fa-chart-line"></i> Analytics
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <?php
                            // Get profile picture if exists
                            $profile_pic = '';
                            if (isset($_SESSION['user_id'])) {
                                $user_id = $_SESSION['user_id'];
                                $pic_query = $conn->query("SELECT profile_picture FROM users WHERE id = $user_id");
                                if ($pic_query && $pic_query->num_rows > 0) {
                                    $profile_pic = $pic_query->fetch_assoc()['profile_picture'];
                                }
                            }
                            ?>
                            <?php if($profile_pic): ?>
                                <img src="../uploads/profiles/<?= $profile_pic ?>" class="user-avatar-small" alt="Profile">
                            <?php else: ?>
                                <i class="fas fa-user-circle"></i>
                            <?php endif; ?>
                            <?= htmlspecialchars($user_name) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?= $user_role == 'admin' ? '../admin/profile.php' : '../user/dashboard.php#profile' ?>">
                                    <i class="fas fa-user"></i> My Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= $user_role == 'admin' ? '../admin/settings.php' : '../user/settings.php' ?>">
                                    <i class="fas fa-cog"></i> Settings
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item logout-btn" href="../auth/logout.php" onclick="return confirmLogout()">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Guest Links -->
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary-custom" href="../auth/register.php" style="background: var(--secondary, #CE9F32); color: #003399; border-radius: 25px; padding: 8px 20px;">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<script>
function confirmLogout() {
    return confirm('Are you sure you want to logout?');
}
</script>

</body>
</html>