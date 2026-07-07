<?php include '../config/db.php'; ?>
<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user data
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

// Get statistics
$stats = $conn->query("SELECT 
    COUNT(*) as total_tests,
    AVG(percentage) as avg_score,
    MAX(percentage) as best_score,
    COUNT(CASE WHEN grade = 'A' THEN 1 END) as a_count,
    COUNT(CASE WHEN grade = 'B' THEN 1 END) as b_count,
    COUNT(CASE WHEN grade = 'C' THEN 1 END) as c_count
FROM results WHERE user_id = $user_id")->fetch_assoc();

// Get recent results
$recent_results = $conn->query("SELECT * FROM results WHERE user_id = $user_id ORDER BY date DESC LIMIT 5");

// Get available categories with counts
$categories = $conn->query("
    SELECT category, COUNT(*) as question_count 
    FROM questions 
    WHERE active = 1 
    GROUP BY category 
    ORDER BY category
");

// Get user's rank
$rank_query = $conn->query("
    SELECT COUNT(DISTINCT user_id) + 1 as rank 
    FROM (
        SELECT user_id, AVG(percentage) as avg_score 
        FROM results 
        GROUP BY user_id 
        HAVING avg_score > (SELECT AVG(percentage) FROM results WHERE user_id = $user_id)
    ) as higher_scores
");
$user_rank = $rank_query->fetch_assoc()['rank'] ?? 1;
$total_students = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'")->fetch_assoc()['total'];

// Define all categories with their properties
$all_categories = [
    'Quantitative Aptitude' => [
        'icon' => 'fa-calculator',
        'color' => '#0d47a1',
        'bg' => '#e3f2fd',
        'description' => 'Numbers, percentages, algebra, arithmetic, data analysis',
        'difficulty' => 'Medium to Hard'
    ],
    'Logical Reasoning' => [
        'icon' => 'fa-puzzle-piece',
        'color' => '#4a148c',
        'bg' => '#f3e5f5',
        'description' => 'Patterns, series, blood relations, coding-decoding, puzzles',
        'difficulty' => 'Medium'
    ],
    'Verbal Ability' => [
        'icon' => 'fa-book',
        'color' => '#1b5e20',
        'bg' => '#e8f5e9',
        'description' => 'Vocabulary, grammar, comprehension, synonyms, antonyms',
        'difficulty' => 'Easy to Medium'
    ],
    'Data Interpretation' => [
        'icon' => 'fa-chart-bar',
        'color' => '#e65100',
        'bg' => '#fff3e0',
        'description' => 'Graphs, charts, tables, data analysis, statistics',
        'difficulty' => 'Medium'
    ],
    'Analytical Reasoning' => [
        'icon' => 'fa-brain',
        'color' => '#00695c',
        'bg' => '#e0f2f1',
        'description' => 'Syllogisms, logical deductions, assumptions, critical thinking',
        'difficulty' => 'Hard'
    ],
    'Spatial Reasoning' => [
        'icon' => 'fa-cube',
        'color' => '#ad1457',
        'bg' => '#fce4ec',
        'description' => 'Mirror images, rotations, paper folding, pattern completion',
        'difficulty' => 'Medium'
    ],
    'Mechanical Reasoning' => [
        'icon' => 'fa-cogs',
        'color' => '#2c3e50',
        'bg' => '#e8eaf6',
        'description' => 'Gears, pulleys, levers, springs, mechanical advantage',
        'difficulty' => 'Medium to Hard'
    ],
    'Abstract Reasoning' => [
        'icon' => 'fa-shapes',
        'color' => '#c62828',
        'bg' => '#ffebee',
        'description' => 'Pattern recognition, analogies, series completion, odd one out',
        'difficulty' => 'Hard'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Dashboard - Smart Aptitude Testing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="manifest" href="../manifest.json">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        .profile-sidebar {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 25px;
            position: sticky;
            top: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .profile-avatar-container {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 15px;
            cursor: pointer;
        }
        
        .profile-avatar {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            transition: all 0.3s;
            object-fit: cover;
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .avatar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: rgba(0,0,0,0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            color: white;
            font-size: 1.5rem;
        }
        
        .profile-avatar-container:hover .avatar-overlay {
            opacity: 1;
        }
        
        .profile-name {
            text-align: center;
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .profile-student-id {
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-bottom: 15px;
        }
        
        .profile-info-item {
            padding: 12px 0;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .profile-info-icon {
            width: 35px;
            color: var(--primary);
            font-size: 1.1rem;
        }
        
        .profile-info-label {
            font-size: 0.7rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .profile-info-value {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .edit-profile-btn {
            width: 100%;
            margin-top: 20px;
            padding: 12px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            transition: all 0.3s;
        }
        
        .edit-profile-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .logout-btn {
            background: #dc3545;
            margin-top: 10px;
        }
        
        .logout-btn:hover {
            background: #c82333;
        }
        
        .stat-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            cursor: pointer;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .category-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .category-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .category-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 1.8rem;
            color: white;
        }
        
        .category-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .category-desc {
            font-size: 0.7rem;
            color: var(--text-secondary);
            margin-bottom: 10px;
            line-height: 1.3;
        }
        
        .category-stats {
            font-size: 0.7rem;
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 10px;
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 20px;
            padding: 25px;
            color: white;
            margin-bottom: 30px;
        }
        
        .rank-badge {
            display: inline-block;
            background: linear-gradient(135deg, #ffd700, #ff8c00);
            color: #333;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .category-all-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            text-align: center;
            padding: 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        
        .category-all-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .toast-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 12px 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 9999;
            animation: slideIn 0.3s ease;
            border-left: 4px solid var(--primary);
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-up {
            animation: fadeInUp 0.6s ease forwards;
        }
        
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        @media (max-width: 768px) {
            .profile-sidebar {
                position: static;
                margin-bottom: 20px;
            }
            .category-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
                gap: 15px;
            }
        }
          /* Grade Display Styles */
.grade-A, .grade-B, .grade-C, .grade-D, .grade-F {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 50px;
    font-weight: bold;
    font-size: 1.8rem;
    min-width: 70px;
    text-align: center;
}

.grade-A { 
    background: linear-gradient(135deg, #28a745, #1e7e34);
    color: white;
    box-shadow: 0 4px 15px rgba(40,167,69,0.3);
}
.grade-B { 
    background: linear-gradient(135deg, #17a2b8, #0f8a9c);
    color: white;
    box-shadow: 0 4px 15px rgba(23,162,184,0.3);
}
.grade-C { 
    background: linear-gradient(135deg, #ffc107, #e0a800);
    color: #333;
    box-shadow: 0 4px 15px rgba(255,193,7,0.3);
}
.grade-D { 
    background: linear-gradient(135deg, #fd7e14, #e86c0c);
    color: white;
    box-shadow: 0 4px 15px rgba(253,126,20,0.3);
}
.grade-F { 
    background: linear-gradient(135deg, #dc3545, #b02a37);
    color: white;
    box-shadow: 0 4px 15px rgba(220,53,69,0.3);
}
    
    /* Grade Badge Styles for Results Table */
.grade-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 0.85rem;
}

.grade-a-badge {
    background: linear-gradient(135deg, #28a745, #1e7e34);
    color: white;
    box-shadow: 0 2px 8px rgba(40,167,69,0.3);
}

.grade-b-badge {
    background: linear-gradient(135deg, #17a2b8, #0f8a9c);
    color: white;
    box-shadow: 0 2px 8px rgba(23,162,184,0.3);
}

.grade-c-badge {
    background: linear-gradient(135deg, #ffc107, #e0a800);
    color: #333;
    box-shadow: 0 2px 8px rgba(255,193,7,0.3);
}

.grade-d-badge {
    background: linear-gradient(135deg, #fd7e14, #e86c0c);
    color: white;
    box-shadow: 0 2px 8px rgba(253,126,20,0.3);
}

.grade-f-badge {
    background: linear-gradient(135deg, #dc3545, #b02a37);
    color: white;
    box-shadow: 0 2px 8px rgba(220,53,69,0.3);
}

.score-display {
    font-size: 0.9rem;
}

.score-percentage {
    color: #666;
    font-size: 0.75rem;
}

.result-row {
    transition: all 0.3s;
}

.result-row:hover {
    background: rgba(var(--primary-rgb), 0.05);
    transform: translateX(3px);
}

/* Grade Summary Card Styles */
.grade-summary-card {
    background: var(--card-bg);
    border-radius: 12px;
    padding: 15px;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.grade-summary-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.grade-summary-letter {
    font-weight: bold;
    font-size: 1rem;
}

.grade-summary-count {
    font-size: 0.7rem;
    color: var(--text-secondary);
}

.grade-summary-score {
    text-align: right;
}

.grade-summary-score strong {
    font-size: 1.2rem;
}

/* Grade Ring Styles */
.grade-ring-card {
    text-align: center;
}

.grade-ring-container {
    display: flex;
    justify-content: center;
    margin-bottom: 10px;
}

.grade-letter-large {
    margin-top: 5px;
}

.grade-A-tag, .grade-B-tag, .grade-C-tag, .grade-D-tag, .grade-F-tag {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 0.8rem;
}

.grade-A-tag { background: #28a745; color: white; }
.grade-B-tag { background: #17a2b8; color: white; }
.grade-C-tag { background: #ffc107; color: #333; }
.grade-D-tag { background: #fd7e14; color: white; }
.grade-F-tag { background: #dc3545; color: white; }
    </style>
</head>
<body data-theme="mixed">
    <div class="container mt-4">
        <div class="row">
            <!-- Profile Sidebar -->
            <div class="col-lg-4 col-md-12 mb-4">
                <div class="profile-sidebar animate-fade-up">
                    <div class="profile-avatar-container" onclick="document.getElementById('avatarUpload').click()">
                        <div class="profile-avatar">
                            <?php if (!empty($user['profile_picture'])): ?>
                                <img src="../uploads/profiles/<?= $user['profile_picture'] ?>" alt="Profile Picture">
                            <?php else: ?>
                                <i class="fas fa-user-graduate"></i>
                            <?php endif; ?>
                        </div>
                        <div class="avatar-overlay">
                            <i class="fas fa-camera"></i>
                        </div>
                    </div>
                    <form id="avatarForm" style="display: none;">
                        <input type="file" id="avatarUpload" name="avatar" accept="image/jpeg,image/png,image/jpg" onchange="uploadAvatar(this)">
                    </form>
                    
                    <div class="profile-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
                    <div class="profile-student-id">
                        <i class="fas fa-id-card"></i> ID: <?= htmlspecialchars($user['student_id']) ?>
                    </div>
                    
                    <div class="profile-info-item">
                        <div class="profile-info-icon"><i class="fas fa-envelope"></i></div>
                        <div>
                            <div class="profile-info-label">Email</div>
                            <div class="profile-info-value"><?= htmlspecialchars($user['email']) ?></div>
                        </div>
                    </div>
                    
                    <div class="profile-info-item">
                        <div class="profile-info-icon"><i class="fas fa-school"></i></div>
                        <div>
                            <div class="profile-info-label">School</div>
                            <div class="profile-info-value"><?= htmlspecialchars($user['school'] ?: 'Not specified') ?></div>
                        </div>
                    </div>
                    
                    <div class="profile-info-item">
                        <div class="profile-info-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <div class="profile-info-label">Region</div>
                            <div class="profile-info-value"><?= htmlspecialchars($user['region'] ?: 'Not specified') ?></div>
                        </div>
                    </div>
                    
                    <div class="profile-info-item">
                        <div class="profile-info-icon"><i class="fas fa-calendar-alt"></i></div>
                        <div>
                            <div class="profile-info-label">Year of Study</div>
                            <div class="profile-info-value"><?= htmlspecialchars($user['year'] ?: 'Not specified') ?></div>
                        </div>
                    </div>
                    
                    <div class="profile-info-item">
                        <div class="profile-info-icon"><i class="fas fa-trophy"></i></div>
                        <div>
                            <div class="profile-info-label">Rank</div>
                            <div class="profile-info-value">
                                <span class="rank-badge">
                                    #<?= $user_rank ?> of <?= $total_students ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-info-item">
                        <div class="profile-info-icon"><i class="fas fa-clock"></i></div>
                        <div>
                            <div class="profile-info-label">Member Since</div>
                            <div class="profile-info-value"><?= date('d M Y', strtotime($user['created_at'] ?? 'now')) ?></div>
                        </div>
                    </div>
                    
                    <button class="edit-profile-btn" onclick="openEditModal()">
                        <i class="fas fa-edit"></i> Edit Profile
                    </button>
                    <button class="edit-profile-btn logout-btn" onclick="logout()">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-8 col-md-12">
                <!-- Welcome Banner -->
                <div class="welcome-banner animate-fade-up">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-6 fw-bold">
                                <i class="fas fa-user-graduate"></i> Welcome, <?= htmlspecialchars($user['first_name']) ?>!
                            </h1>
                            <p class="mb-0">Discover your potential with our comprehensive aptitude testing system.</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <i class="fas fa-brain fa-4x float-animation"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <!-- Stats Cards with Grade Display -->
<div class="row mb-4">
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card" onclick="location.href='certificates.php'">
            <div class="stat-icon"><i class="fas fa-trophy text-warning"></i></div>
            <div class="stat-number"><?= $stats['total_tests'] ?? 0 ?></div>
            <div class="small">Tests Taken</div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card" onclick="location.href='certificates.php'">
            <div class="stat-icon"><i class="fas fa-percent text-success"></i></div>
            <div class="stat-number"><?= round($stats['avg_score'] ?? 0, 1) ?>%</div>
            <div class="small">Average Score</div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card" onclick="location.href='certificates.php'">
            <div class="stat-icon"><i class="fas fa-star text-info"></i></div>
            <div class="stat-number">
                <?php 
                // Calculate overall grade based on average score
                $avg_score = $stats['avg_score'] ?? 0;
                if ($avg_score >= 80) {
                    echo '<span class="grade-A">A</span>';
                } elseif ($avg_score >= 70) {
                    echo '<span class="grade-B">B</span>';
                } elseif ($avg_score >= 60) {
                    echo '<span class="grade-C">C</span>';
                } elseif ($avg_score >= 50) {
                    echo '<span class="grade-D">D</span>';
                } else {
                    echo '<span class="grade-F">F</span>';
                }
                ?>
            </div>
            <div class="small">Overall Grade</div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="stat-card" onclick="location.href='certificates.php'">
            <div class="stat-icon"><i class="fas fa-chart-simple"></i></div>
            <div class="stat-number"><?= round(($stats['best_score'] ?? 0), 1) ?>%</div>
            <div class="small">Best Score</div>
        </div>
    </div>
</div>
                
                <!-- Browse All Categories Button -->
                <div class="category-all-btn animate-fade-up" onclick="location.href='all_categories.php'" style="animation-delay: 0.1s;">
                    <i class="fas fa-th-large fa-2x mb-2"></i>
                    <h4 class="mb-1">Browse All Categories</h4>
                    <p class="mb-0 small">View detailed information about all aptitude test categories</p>
                </div>
                
                <!-- Categories Grid -->
                <h4 class="mb-3 animate-fade-up" style="animation-delay: 0.2s;">
                    <i class="fas fa-layer-group"></i> Test Categories
                </h4>
                <div class="category-grid">
                    <?php 
                    // Reset category pointer
                    $categories->data_seek(0);
                    $displayed_categories = [];
                    while($cat = $categories->fetch_assoc()): 
                        $displayed_categories[] = $cat['category'];
                        $cat_info = $all_categories[$cat['category']] ?? [
                            'icon' => 'fa-brain', 
                            'color' => '#6c757d', 
                            'bg' => '#f8f9fa',
                            'description' => 'Test your skills',
                            'difficulty' => 'Varied'
                        ];
                    ?>
                        <div class="category-card animate-fade-up" onclick="startTest('<?= htmlspecialchars($cat['category']) ?>')" style="animation-delay: <?= 0.3 + (count($displayed_categories) * 0.05) ?>s;">
                            <div class="category-icon" style="background: <?= $cat_info['color'] ?>;">
                                <i class="fas <?= $cat_info['icon'] ?>"></i>
                            </div>
                            <div class="category-title"><?= htmlspecialchars($cat['category']) ?></div>
                            <div class="category-desc"><?= $cat_info['description'] ?></div>
                            <div class="category-stats">
                                <span><i class="fas fa-database"></i> <?= $cat['question_count'] ?> Qs</span>
                                <span><i class="fas fa-chart-line"></i> <?= $cat_info['difficulty'] ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <!-- Certificate Grade Summary -->
<?php
// Get certificate grades summary
$certificate_grades = $conn->query("
    SELECT 
        r.grade,
        COUNT(*) as count,
        AVG(r.percentage) as avg_score,
        MAX(r.percentage) as best_in_grade,
        MIN(r.percentage) as worst_in_grade
    FROM results r
    WHERE r.user_id = $user_id
    GROUP BY r.grade
    ORDER BY FIELD(r.grade, 'A', 'B', 'C', 'D', 'F')
");
?>
<div class="card mb-4 animate-fade-up" style="animation-delay: 0.4s;">
    <div class="card-header bg-transparent">
        <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Certificate Grade Summary</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php while($grade = $certificate_grades->fetch_assoc()): 
                $grade_color = '';
                $grade_icon = '';
                if ($grade['grade'] == 'A') {
                    $grade_color = '#28a745';
                    $grade_icon = 'fas fa-crown';
                } elseif ($grade['grade'] == 'B') {
                    $grade_color = '#17a2b8';
                    $grade_icon = 'fas fa-star';
                } elseif ($grade['grade'] == 'C') {
                    $grade_color = '#ffc107';
                    $grade_icon = 'fas fa-check-circle';
                } elseif ($grade['grade'] == 'D') {
                    $grade_color = '#fd7e14';
                    $grade_icon = 'fas fa-chart-line';
                } else {
                    $grade_color = '#dc3545';
                    $grade_icon = 'fas fa-graduation-cap';
                }
            ?>
            <div class="col-md-4 col-6 mb-3">
                <div class="grade-summary-card" style="border-left: 4px solid <?= $grade_color ?>;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="grade-summary-letter" style="color: <?= $grade_color ?>;">
                                <i class="<?= $grade_icon ?>"></i> Grade <?= $grade['grade'] ?>
                            </span>
                            <div class="grade-summary-count"><?= $grade['count'] ?> certificate(s)</div>
                        </div>
                        <div class="grade-summary-score">
                            <div class="small text-muted">Average</div>
                            <strong><?= round($grade['avg_score'], 1) ?>%</strong>
                        </div>
                    </div>
                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar" style="width: <?= $grade['avg_score'] ?>%; background: <?= $grade_color ?>;"></div>
                    </div>
                    <div class="row mt-2 small text-muted">
                        <div class="col-6">Best: <?= round($grade['best_in_grade'], 1) ?>%</div>
                        <div class="col-6 text-end">Worst: <?= round($grade['worst_in_grade'], 1) ?>%</div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>
                <!-- Quick Mixed Test -->
                <div class="category-all-btn animate-fade-up" onclick="startQuickTest()" style="background: linear-gradient(135deg, #CE9F32, #b8860b); margin-bottom: 20px;">
                    <i class="fas fa-bolt fa-2x mb-2"></i>
                    <h4 class="mb-1">Quick Mixed Test</h4>
                    <p class="mb-0 small">10 random questions from all categories - Quick assessment</p>
                </div>
                
                <!-- Recent Results -->
              <!-- Recent Results -->
<?php if($recent_results->num_rows > 0): ?>
<h4 class="mb-3"><i class="fas fa-history"></i> Recent Test Results</h4>
<div class="card mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Score</th>
                        <th>Grade</th>
                        <th>Certificate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $recent_results->fetch_assoc()): 
                        $grade_class = '';
                        $grade_icon = '';
                        if ($row['grade'] == 'A') {
                            $grade_class = 'grade-a-badge';
                            $grade_icon = 'fas fa-crown';
                        } elseif ($row['grade'] == 'B') {
                            $grade_class = 'grade-b-badge';
                            $grade_icon = 'fas fa-star';
                        } elseif ($row['grade'] == 'C') {
                            $grade_class = 'grade-c-badge';
                            $grade_icon = 'fas fa-check-circle';
                        } elseif ($row['grade'] == 'D') {
                            $grade_class = 'grade-d-badge';
                            $grade_icon = 'fas fa-exclamation-circle';
                        } else {
                            $grade_class = 'grade-f-badge';
                            $grade_icon = 'fas fa-times-circle';
                        }
                    ?>
                    <tr class="result-row">
                        <td class="text-nowrap"><?= date('d M Y', strtotime($row['date'])) ?></td>
                        <td><?= htmlspecialchars($row['category']) ?></td>
                        <td>
                            <div class="score-display">
                                <strong><?= $row['score'] ?></strong> / <?= $row['total'] ?>
                                <span class="score-percentage">(<?= round($row['percentage']) ?>%)</span>
                            </div>
                            <div class="progress mt-1" style="height: 5px;">
                                <div class="progress-bar progress-bar-custom" style="width: <?= $row['percentage'] ?>%;"></div>
                            </div>
                        </td>
                        <td>
                            <span class="grade-badge <?= $grade_class ?>">
                                <i class="<?= $grade_icon ?>"></i> <?= $row['grade'] ?>
                            </span>
                        </td>
                        <td>
                            <a href="certificate.php?result=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="fas fa-certificate"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
                   
            </div>
        </div>
    </div>
    
    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white;">
                    <h5 class="modal-title"><i class="fas fa-user-edit"></i> Edit Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="editMessage" class="alert" style="display: none;"></div>
                    <form id="editProfileForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" id="edit_first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" id="edit_last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="edit_email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">School</label>
                                <input type="text" name="school" id="edit_school" class="form-control" value="<?= htmlspecialchars($user['school']) ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Region</label>
                                <select name="region" id="edit_region" class="form-select">
                                    <option value="">Select Region</option>
                                    <option value="Hhohho" <?= $user['region'] == 'Hhohho' ? 'selected' : '' ?>>Hhohho</option>
                                    <option value="Manzini" <?= $user['region'] == 'Manzini' ? 'selected' : '' ?>>Manzini</option>
                                    <option value="Shiselweni" <?= $user['region'] == 'Shiselweni' ? 'selected' : '' ?>>Shiselweni</option>
                                    <option value="Lubombo" <?= $user['region'] == 'Lubombo' ? 'selected' : '' ?>>Lubombo</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Year of Study</label>
                                <select name="year" id="edit_year" class="form-select">
                                    <option value="">Select Year</option>
                                    <option value="Form 4" <?= $user['year'] == 'Form 4' ? 'selected' : '' ?>>Form 4</option>
                                    <option value="Form 5" <?= $user['year'] == 'Form 5' ? 'selected' : '' ?>>Form 5</option>
                                    <option value="University" <?= $user['year'] == 'University' ? 'selected' : '' ?>>University</option>
                                    <option value="Graduate" <?= $user['year'] == 'Graduate' ? 'selected' : '' ?>>Graduate</option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">New Password (leave blank to keep current)</label>
                                <input type="password" name="password" id="edit_password" class="form-control" placeholder="Enter new password">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveProfile()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Grade Ring Display -->
<div class="col-md-3 col-6 mb-3">
    <div class="stat-card grade-ring-card" onclick="location.href='certificates.php'">
        <div class="grade-ring-container">
            <svg width="80" height="80" viewBox="0 0 100 100">
                <circle cx="50" cy="50" r="42" fill="none" stroke="#e0e0e0" stroke-width="6"/>
                <circle cx="50" cy="50" r="42" fill="none" 
                    stroke="<?php 
                        $avg = $stats['avg_score'] ?? 0;
                        if ($avg >= 80) echo '#28a745';
                        elseif ($avg >= 70) echo '#17a2b8';
                        elseif ($avg >= 60) echo '#ffc107';
                        elseif ($avg >= 50) echo '#fd7e14';
                        else echo '#dc3545';
                    ?>" 
                    stroke-width="6" 
                    stroke-dasharray="<?= ($avg / 100) * 264 ?>" 
                    stroke-dashoffset="264"
                    stroke-linecap="round"
                    transform="rotate(-90 50 50)"/>
                <text x="50" y="55" text-anchor="middle" font-size="20" font-weight="bold" fill="var(--text-primary)">
                    <?= round($avg) ?>%
                </text>
            </svg>
        </div>
        <div class="small mt-2">Overall Grade</div>
        <div class="grade-letter-large">
            <?php 
            if ($avg >= 80) echo '<span class="grade-A-tag">A</span>';
            elseif ($avg >= 70) echo '<span class="grade-B-tag">B</span>';
            elseif ($avg >= 60) echo '<span class="grade-C-tag">C</span>';
            elseif ($avg >= 50) echo '<span class="grade-D-tag">D</span>';
            else echo '<span class="grade-F-tag">F</span>';
            ?>
        </div>
    </div>
</div>
    <!-- Theme Switcher -->
    <div class="theme-switch" onclick="toggleTheme()">
        <i class="fas fa-palette"></i> <span id="themeLabel">Theme</span>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let editModal;
        
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../auth/logout.php';
            }
        }
        
        function openEditModal() {
            editModal = new bootstrap.Modal(document.getElementById('editProfileModal'));
            editModal.show();
        }
        
        function saveProfile() {
            const formData = new FormData(document.getElementById('editProfileForm'));
            const messageDiv = document.getElementById('editMessage');
            
            fetch('update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                messageDiv.style.display = 'block';
                if (data.success) {
                    messageDiv.className = 'alert alert-success';
                    messageDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                    setTimeout(() => location.reload(), 1500);
                } else {
                    messageDiv.className = 'alert alert-danger';
                    messageDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
                }
            });
        }
        
        function uploadAvatar(input) {
            if (input.files && input.files[0]) {
                const formData = new FormData();
                formData.append('avatar', input.files[0]);
                
                fetch('upload_avatar.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
            }
        }
        
        function startTest(category) {
            window.location.href = `test.php?category=${encodeURIComponent(category)}`;
        }
        
        function startQuickTest() {
            window.location.href = 'test.php?quick=1';
        }
        
        let currentTheme = localStorage.getItem('aptitude_theme') || 'mixed';
        
        function applyTheme(themeName) {
            document.body.setAttribute('data-theme', themeName);
            localStorage.setItem('aptitude_theme', themeName);
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
        
        applyTheme(currentTheme);
    </script>
</body>
</html>