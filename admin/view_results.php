<?php include '../config/db.php'; ?>
<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle filter by student
$student_filter = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$search_filter = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Build query
$query = "
    SELECT 
        r.*, 
        u.first_name, 
        u.last_name, 
        u.student_id, 
        u.school,
        u.region
    FROM results r 
    JOIN users u ON r.user_id = u.id 
    WHERE u.role = 'user'
";

if ($student_filter > 0) {
    $query .= " AND u.id = $student_filter";
}

if (!empty($search_filter)) {
    $query .= " AND (u.first_name LIKE '%$search_filter%' 
                OR u.last_name LIKE '%$search_filter%' 
                OR u.student_id LIKE '%$search_filter%' 
                OR u.school LIKE '%$search_filter%')";
}

$query .= " ORDER BY r.date DESC, u.school, u.first_name";

$results = $conn->query($query);

// Get all students for dropdown filter
$students = $conn->query("SELECT id, first_name, last_name, student_id, school FROM users WHERE role = 'user' ORDER BY school, first_name");

// Get statistics
$total_results = $conn->query("SELECT COUNT(*) as total FROM results r JOIN users u ON r.user_id = u.id WHERE u.role = 'user'")->fetch_assoc()['total'];
$avg_score = $conn->query("SELECT ROUND(AVG(percentage), 1) as avg FROM results r JOIN users u ON r.user_id = u.id WHERE u.role = 'user'")->fetch_assoc()['avg'];
$total_students = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Results - SATS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        body { background: #f0f2f5; }
        
        .stat-card {
            background: var(--card-bg, white);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary, #003399);
        }
        
        .filter-bar {
            background: var(--card-bg, white);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .table-container {
            background: var(--card-bg, white);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .grade-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
        }
        
        .grade-A { background: #28a745; color: white; }
        .grade-B { background: #17a2b8; color: white; }
        .grade-C { background: #ffc107; color: #333; }
        .grade-D { background: #fd7e14; color: white; }
        .grade-F { background: #dc3545; color: white; }
        .grade-0 { background: #6c757d; color: white; }
        
        .btn-view-cert {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .btn-view-cert:hover {
            transform: scale(1.05);
        }
        
        .student-link {
            color: var(--primary, #003399);
            text-decoration: none;
            font-weight: 600;
        }
        
        .student-link:hover {
            text-decoration: underline;
        }
        
        .score-display {
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .stat-number { font-size: 1.5rem; }
        }
    </style>
</head>
<body data-theme="mixed">
    <div class="container mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-chart-line"></i> All Test Results</h2>
            <div>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="../auth/logout.php" class="btn btn-danger ms-2" onclick="return confirm('Logout?')">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-number"><?= $total_results ?></div>
                    <div><i class="fas fa-file-alt"></i> Total Results</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-number"><?= $avg_score ?? 0 ?>%</div>
                    <div><i class="fas fa-percent"></i> Avg Score</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-number"><?= $total_students ?></div>
                    <div><i class="fas fa-users"></i> Students</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-number">
                        <?php
                        $cert_count = $conn->query("SELECT COUNT(*) as total FROM certificates")->fetch_assoc()['total'];
                        echo $cert_count;
                        ?>
                    </div>
                    <div><i class="fas fa-certificate"></i> Certificates</div>
                </div>
            </div>
        </div>
        
        <!-- Filter Bar -->
        <div class="filter-bar">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label"><i class="fas fa-user-graduate"></i> Student</label>
                    <select name="student_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Students</option>
                        <?php while($s = $students->fetch_assoc()): ?>
                            <option value="<?= $s['id'] ?>" <?= $student_filter == $s['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name'] . ' (' . $s['student_id'] . ')') ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label"><i class="fas fa-search"></i> Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search by name, ID, or school..." value="<?= htmlspecialchars($search_filter) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i> Filter</button>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <a href="view_results.php" class="btn btn-secondary w-100"><i class="fas fa-undo"></i> Clear</a>
                </div>
            </form>
        </div>
        
        <!-- Results Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>School</th>
                            <th>Topic</th>
                            <th>Score</th>
                            <th>Grade</th>
                            <th>Certificate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($results && $results->num_rows > 0): ?>
                            <?php while($row = $results->fetch_assoc()): 
                                // Determine grade badge class
                                $grade_class = 'grade-' . ($row['grade'] ?: '0');
                                $grade_display = $row['grade'] ?: 'N/A';
                                
                                // Format percentage
                                $percentage = $row['percentage'] ? round($row['percentage']) : 0;
                                $score_display = $row['score'] . '/' . $row['total'] . ' (' . $percentage . '%)';
                                
                                // Get grade color for icon
                                $grade_color = '';
                                if ($row['grade'] == 'A') $grade_color = '#28a745';
                                elseif ($row['grade'] == 'B') $grade_color = '#17a2b8';
                                elseif ($row['grade'] == 'C') $grade_color = '#ffc107';
                                elseif ($row['grade'] == 'D') $grade_color = '#fd7e14';
                                elseif ($row['grade'] == 'F') $grade_color = '#dc3545';
                                else $grade_color = '#6c757d';
                            ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($row['date'])) ?></td>
                                <td><strong><?= htmlspecialchars($row['student_id']) ?></strong></td>
                                <td>
                                    <a href="view_results.php?student_id=<?= $row['user_id'] ?>" class="student-link">
                                        <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($row['school'] ?: 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['topic'] ?: $row['category']) ?></td>
                                <td class="score-display"><?= $score_display ?></td>
                                <td>
                                    <span class="grade-badge <?= $grade_class ?>">
                                        <?= $grade_display ?>
                                    </span>
                                </td>
                                <td>
                                    <!-- UPDATED: View Certificate button links to admin certificate view -->
                                    <a href="view_certificate.php?user_id=<?= $row['user_id'] ?>" 
                                       class="btn btn-sm btn-primary btn-view-cert" 
                                       target="_blank"
                                       title="View Combined Certificate for <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>">
                                        <i class="fas fa-certificate"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                    <p class="text-muted">No results found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Results Count -->
            <div class="mt-3 text-muted small">
                <i class="fas fa-info-circle"></i> Showing <?= $results->num_rows ?> result(s)
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme Management
        let currentTheme = localStorage.getItem('aptitude_theme') || 'mixed';
        
        function applyTheme(themeName) {
            document.body.setAttribute('data-theme', themeName);
            localStorage.setItem('aptitude_theme', themeName);
            document.body.classList.add('theme-transition');
            setTimeout(() => document.body.classList.remove('theme-transition'), 500);
        }
        
        applyTheme(currentTheme);
    </script>
</body>
</html>