<?php include '../config/db.php'; ?>
<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Get statistics
$total_users = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user'")->fetch_assoc()['c'];
$total_tests = $conn->query("SELECT COUNT(*) as c FROM results")->fetch_assoc()['c'];
$total_questions = $conn->query("SELECT COUNT(*) as c FROM questions")->fetch_assoc()['c'];
$avg_score = $conn->query("SELECT AVG(percentage) as a FROM results")->fetch_assoc()['a'];

// Get recent activities
$recent_users = $conn->query("SELECT * FROM users WHERE role='user' ORDER BY created_at DESC LIMIT 5");
$recent_tests = $conn->query("SELECT r.*, u.first_name, u.last_name FROM results r JOIN users u ON r.user_id = u.id ORDER BY r.date DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Smart Aptitude Testing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .stat-card {
            background: var(--card-bg, white);
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            cursor: pointer;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: var(--primary, #003399);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary, #003399);
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: var(--text-secondary, #666);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .menu-card {
            background: var(--card-bg, white);
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            margin-bottom: 20px;
            height: 100%;
        }
        
        .menu-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
        }
        
        .menu-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--primary, #003399);
        }
        
        .welcome-admin {
            background: linear-gradient(135deg, var(--primary, #003399), var(--secondary, #CE9F32));
            border-radius: 20px;
            padding: 25px;
            color: white;
            margin-bottom: 30px;
        }
        
        .recent-card {
            background: var(--card-bg, white);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .btn-custom {
            border-radius: 12px;
            padding: 10px 24px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
        }
        
        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-up {
            animation: fadeInUp 0.5s ease forwards;
        }
        
        .table-hover tbody tr:hover {
            background: rgba(var(--primary-rgb, 0,51,153), 0.05);
        }
    </style>
</head>
<body data-theme="mixed">
    <div class="container mt-4">
        <!-- Welcome Admin Banner with Logout Button -->
        <div class="welcome-admin animate-fade-up">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <div class="admin-avatar">
                            <i class="fas fa-user-shield fa-lg"></i>
                        </div>
                        <div>
                            <h2 class="mb-0">
                                <i class="fas fa-crown"></i> Admin Dashboard
                            </h2>
                            <p class="mb-0 mt-2">
                                Welcome back, <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Administrator') ?></strong>! 
                                Manage the aptitude testing system from here.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <a href="../admin/dashboard.php" class="btn btn-light btn-custom me-2">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="../auth/logout.php" class="btn btn-danger btn-custom" onclick="return confirm('Are you sure you want to logout?')">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4 stagger-children">
            <div class="col-md-3 col-6 mb-3">
                <div class="stat-card" onclick="location.href='view_results.php'">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?= number_format($total_users) ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="stat-card" onclick="location.href='view_results.php'">
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-number"><?= number_format($total_tests) ?></div>
                    <div class="stat-label">Tests Completed</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="stat-card" onclick="location.href='manage_questions.php'">
                    <div class="stat-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="stat-number"><?= number_format($total_questions) ?></div>
                    <div class="stat-label">Questions</div>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="stat-card" onclick="location.href='analytics.php'">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-number"><?= round($avg_score, 1) ?>%</div>
                    <div class="stat-label">Avg Score</div>
                </div>
            </div>
        </div>
        
        <!-- Admin Menu Cards -->
        <h4 class="mb-3 animate-fade-up" style="animation-delay: 0.2s;">
            <i class="fas fa-cogs"></i> Administration Tools
        </h4>
        <div class="row mt-3 stagger-children">
            <div class="col-md-4 col-6 mb-3">
                <div class="menu-card" onclick="location.href='manage_questions.php'">
                    <div class="menu-icon"><i class="fas fa-database"></i></div>
                    <h5>Manage Questions</h5>
                    <small class="text-muted">Add, edit, delete questions</small>
                </div>
            </div>
            <div class="col-md-4 col-6 mb-3">
                <div class="menu-card" onclick="location.href='add_question.php'">
                    <div class="menu-icon"><i class="fas fa-plus-circle"></i></div>
                    <h5>Add Question</h5>
                    <small class="text-muted">Create new test items</small>
                </div>
            </div>
            <div class="col-md-4 col-6 mb-3">
                <div class="menu-card" onclick="location.href='view_results.php'">
                    <div class="menu-icon"><i class="fas fa-chart-line"></i></div>
                    <h5>View Results</h5>
                    <small class="text-muted">All student results</small>
                </div>
            </div>
            <div class="col-md-4 col-6 mb-3">
                <div class="menu-card" onclick="location.href='analytics.php'">
                    <div class="menu-icon"><i class="fas fa-chart-pie"></i></div>
                    <h5>Analytics</h5>
                    <small class="text-muted">System analytics & IRT</small>
                </div>
            </div>
            <div class="col-md-4 col-6 mb-3">
                <div class="menu-card" onclick="location.href='school_analytics.php'">
                    <div class="menu-icon"><i class="fas fa-school"></i></div>
                    <h5>School Analytics</h5>
                    <small class="text-muted">School performance reports</small>
                </div>
            </div>
            <div class="col-md-4 col-6 mb-3">
                <div class="menu-card" onclick="location.href='proctoring.php'">
                    <div class="menu-icon"><i class="fas fa-eye"></i></div>
                    <h5>Live Proctoring</h5>
                    <small class="text-muted">Monitor active tests</small>
                </div>
            </div>
            <div class="col-md-4 col-6 mb-3">
                <div class="menu-card" onclick="location.href='system_logs.php'">
                    <div class="menu-icon"><i class="fas fa-history"></i></div>
                    <h5>System Logs</h5>
                    <small class="text-muted">Audit trail</small>
                </div>
            </div>
            <div class="col-md-4 col-6 mb-3">
                <div class="menu-card" onclick="location.href='../user/dashboard.php'">
                    <div class="menu-icon"><i class="fas fa-user-graduate"></i></div>
                    <h5>Student View</h5>
                    <small class="text-muted">View as student</small>
                </div>
            </div>
            <div class="col-md-4 col-6 mb-3">
                <div class="menu-card" onclick="location.href='qr_bulk_generator.php'">
                    <div class="menu-icon"><i class="fas fa-qrcode"></i></div>
                    <h5>QR Generator</h5>
                    <small class="text-muted">Bulk QR code generation</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6 mb-3">
    <div class="menu-card" onclick="location.href='manage_students.php'">
        <div class="menu-icon"><i class="fas fa-users"></i></div>
        <h5>Manage Students</h5>
        <small class="text-muted">View, edit, delete student profiles</small>
    </div>
</div>
        <!-- Recent Users Section -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="recent-card animate-fade-up" style="animation-delay: 0.3s;">
                    <h5><i class="fas fa-user-plus"></i> Recent Registrations</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr><th>Name</th><th>Student ID</th><th>School</th><th>Date</th></tr>
                            </thead>
                            <tbody>
                                <?php while($user = $recent_users->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                                    <td><?= htmlspecialchars($user['student_id']) ?></td>
                                    <td><?= htmlspecialchars($user['school'] ?: '-') ?></td>
                                    <td><?= date('d M', strtotime($user['created_at'])) ?></td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if($recent_users->num_rows == 0): ?>
                                <tr><td colspan="4" class="text-center">No users yet</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="recent-card animate-fade-up" style="animation-delay: 0.4s;">
                    <h5><i class="fas fa-clock"></i> Recent Test Activity</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr><th>Student</th><th>Category</th><th>Score</th><th>Date</th></tr>
                            </thead>
                            <tbody>
                                <?php while($test = $recent_tests->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($test['first_name'] . ' ' . $test['last_name']) ?></td>
                                    <td><?= htmlspecialchars($test['category']) ?></td>
                                    <td><?= $test['score'] ?>/<?= $test['total'] ?> (<?= round($test['percentage']) ?>%)</td>
                                    <td><?= date('d M H:i', strtotime($test['date'])) ?></td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if($recent_tests->num_rows == 0): ?>
                                <tr><td colspan="4" class="text-center">No tests taken yet</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions Footer -->
        <div class="row mt-3 mb-5">
            <div class="col-12 text-center">
                <div class="recent-card">
                    <h5><i class="fas fa-question-circle"></i> Need Help?</h5>
                    <p class="text-muted mb-0">For assistance, contact the system administrator or refer to the documentation.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Theme Switcher -->
    <div class="theme-switch" onclick="toggleTheme()">
        <i class="fas fa-palette"></i> <span id="themeLabel">Theme</span>
    </div>
    
    <script>
        // Theme Management
        let currentTheme = localStorage.getItem('aptitude_theme') || 'mixed';
        
        function applyTheme(themeName) {
            document.body.setAttribute('data-theme', themeName);
            localStorage.setItem('aptitude_theme', themeName);
            
            const themeNames = {
                'quantitative': '🔢 Quantitative',
                'logical': '🧩 Logical',
                'verbal': '📚 Verbal',
                'mixed': '🎨 Mixed',
                'dark': '🌙 Dark'
            };
            const themeLabel = document.getElementById('themeLabel');
            if (themeLabel) themeLabel.textContent = themeNames[themeName] || 'Theme';
            
            document.body.classList.add('theme-transition');
            setTimeout(() => document.body.classList.remove('theme-transition'), 500);
        }
        
        function toggleTheme() {
            const themes = ['mixed', 'quantitative', 'logical', 'verbal', 'dark'];
            let currentIndex = themes.indexOf(currentTheme);
            let nextIndex = (currentIndex + 1) % themes.length;
            currentTheme = themes[nextIndex];
            applyTheme(currentTheme);
        }
        
        // Apply saved theme
        applyTheme(currentTheme);
        
        // Stagger children animation
        document.querySelectorAll('.stagger-children').forEach(container => {
            const children = container.children;
            Array.from(children).forEach((child, index) => {
                child.style.opacity = '0';
                child.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    child.style.transition = 'all 0.5s ease';
                    child.style.opacity = '1';
                    child.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
        
        // Auto refresh dashboard every 60 seconds (optional)
        let autoRefresh = true;
        if (autoRefresh) {
            setTimeout(() => {
                location.reload();
            }, 60000);
        }
    </script>
</body>
</html>