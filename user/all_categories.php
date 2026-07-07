<?php include '../config/db.php'; ?>
<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all categories with question counts
$categories = $conn->query("
    SELECT 
        category,
        COUNT(*) as total_questions,
        COUNT(CASE WHEN difficulty < 0 THEN 1 END) as easy_questions,
        COUNT(CASE WHEN difficulty BETWEEN 0 AND 1 THEN 1 END) as medium_questions,
        COUNT(CASE WHEN difficulty > 1 THEN 1 END) as hard_questions,
        ROUND(AVG(difficulty), 2) as avg_difficulty,
        MIN(difficulty) as min_difficulty,
        MAX(difficulty) as max_difficulty
    FROM questions 
    WHERE active = 1 
    GROUP BY category
    ORDER BY category
");

// Get user's performance by category
$user_performance = $conn->query("
    SELECT 
        category,
        COUNT(*) as tests_taken,
        ROUND(AVG(percentage), 1) as avg_score,
        MAX(percentage) as best_score,
        MIN(percentage) as worst_score
    FROM results 
    WHERE user_id = $user_id
    GROUP BY category
");

// Create performance lookup array
$performance = [];
while ($row = $user_performance->fetch_assoc()) {
    $performance[$row['category']] = $row;
}

// Get total questions count for timer calculation
$total_questions_all = $conn->query("SELECT COUNT(*) as total FROM questions WHERE active = 1")->fetch_assoc()['total'];

// Get topic breakdown by category
$topics_by_category = $conn->query("
    SELECT category, topic, COUNT(*) as count
    FROM questions
    WHERE active = 1
    GROUP BY category, topic
    ORDER BY category, count DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>All Categories - SATS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        body {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            min-height: 100vh;
        }
        
        .category-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 20px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
        }
        
        .category-card-large {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .category-card-large:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .category-icon-large {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
        }
        
        .stat-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin: 3px;
        }
        
        .stat-badge-easy { background: #28a745; color: white; }
        .stat-badge-medium { background: #ffc107; color: #333; }
        .stat-badge-hard { background: #dc3545; color: white; }
        
        .topic-tag {
            display: inline-block;
            background: #e9ecef;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.7rem;
            margin: 3px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .topic-tag:hover {
            background: var(--primary);
            color: white;
            transform: scale(1.05);
        }
        
        .progress-custom {
            height: 8px;
            border-radius: 10px;
            background: #e0e0e0;
        }
        
        .progress-bar-custom {
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 10px;
        }
        
        .timer-info {
            background: linear-gradient(135deg, #003399, #001a66);
            color: white;
            padding: 15px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        
        .test-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        .btn-test {
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-test:hover {
            transform: translateY(-2px);
        }
        
        .score-excellent { color: #28a745; font-weight: bold; }
        .score-good { color: #17a2b8; font-weight: bold; }
        .score-average { color: #ffc107; font-weight: bold; }
        .score-poor { color: #dc3545; font-weight: bold; }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-up {
            animation: fadeInUp 0.5s ease forwards;
        }
        
        @media (max-width: 768px) {
            .category-header h1 { font-size: 1.5rem; }
            .category-icon-large { width: 50px; height: 50px; font-size: 1.5rem; }
        }
    </style>
</head>
<body data-theme="mixed">
    <div class="container mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <a href="../auth/logout.php" class="btn btn-danger" onclick="return confirm('Logout?')">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        
        <!-- Category Header -->
        <div class="category-header animate-fade-up">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-th-large"></i> All Test Categories</h1>
                    <p class="mb-0">Browse through all available aptitude categories. Select a category to start your adaptive test.</p>
                </div>
                <div class="col-md-4 text-center">
                    <i class="fas fa-layer-group fa-4x float-animation"></i>
                </div>
            </div>
        </div>
        
        <!-- Timer Info Banner -->
        <div class="timer-info animate-fade-up" style="animation-delay: 0.1s;">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <i class="fas fa-clock"></i> <strong>Time Calculation:</strong> Each test's duration is based on the number of questions available in that category.
                </div>
                <div class="col-md-6 text-md-end">
                    <i class="fas fa-chart-line"></i> <strong>Adaptive Testing:</strong> Questions adjust to your skill level!
                </div>
            </div>
        </div>
        
        <!-- Categories List -->
        <?php 
        $category_icons = [
            'Quantitative Aptitude' => ['icon' => 'fa-calculator', 'color' => '#0d47a1', 'bg' => '#e3f2fd'],
            'Logical Reasoning' => ['icon' => 'fa-puzzle-piece', 'color' => '#4a148c', 'bg' => '#f3e5f5'],
            'Verbal Ability' => ['icon' => 'fa-book', 'color' => '#1b5e20', 'bg' => '#e8f5e9']
        ];
        
        $index = 0;
        while($cat = $categories->fetch_assoc()): 
            $icon_data = $category_icons[$cat['category']] ?? ['icon' => 'fa-brain', 'color' => '#003399', 'bg' => '#e9ecef'];
            $user_perf = $performance[$cat['category']] ?? null;
            $time_minutes = max(5, ceil($cat['total_questions'] * 0.75)); // 45 seconds per question, min 5 minutes
            $time_display = $time_minutes . ' min';
            if ($time_minutes >= 60) {
                $time_display = floor($time_minutes / 60) . 'h ' . ($time_minutes % 60) . 'min';
            }
            
            $score_class = '';
            $score_text = '';
            if ($user_perf && $user_perf['avg_score']) {
                $avg = $user_perf['avg_score'];
                if ($avg >= 70) { $score_class = 'score-excellent'; $score_text = 'Excellent'; }
                elseif ($avg >= 55) { $score_class = 'score-good'; $score_text = 'Good'; }
                elseif ($avg >= 40) { $score_class = 'score-average'; $score_text = 'Average'; }
                else { $score_class = 'score-poor'; $score_text = 'Needs Improvement'; }
            }
        ?>
        <div class="category-card-large animate-fade-up" style="animation-delay: <?= 0.2 + ($index * 0.1) ?>s;">
            <div class="row align-items-center">
                <div class="col-md-2 col-3 text-center">
                    <div class="category-icon-large" style="background: <?= $icon_data['color'] ?>;">
                        <i class="fas <?= $icon_data['icon'] ?>"></i>
                    </div>
                </div>
                <div class="col-md-5 col-9">
                    <h3 class="mb-1"><?= htmlspecialchars($cat['category']) ?></h3>
                    <p class="text-muted mb-2">
                        <i class="fas fa-database"></i> <?= $cat['total_questions'] ?> questions available
                    </p>
                    <div>
                        <span class="stat-badge stat-badge-easy">
                            <i class="fas fa-smile"></i> Easy: <?= $cat['easy_questions'] ?>
                        </span>
                        <span class="stat-badge stat-badge-medium">
                            <i class="fas fa-meh"></i> Medium: <?= $cat['medium_questions'] ?>
                        </span>
                        <span class="stat-badge stat-badge-hard">
                            <i class="fas fa-frown"></i> Hard: <?= $cat['hard_questions'] ?>
                        </span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-2">
                        <i class="fas fa-chart-line"></i> Difficulty Range: 
                        <strong><?= number_format($cat['min_difficulty'], 1) ?> → <?= number_format($cat['max_difficulty'], 1) ?></strong>
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-clock"></i> Estimated Time: 
                        <strong><span class="text-primary"><?= $time_display ?></span></strong>
                        <small>(<?= $cat['total_questions'] ?> questions)</small>
                    </div>
                    <?php if ($user_perf): ?>
                    <div class="mt-2">
                        <i class="fas fa-trophy"></i> Your Best: <strong><?= $user_perf['best_score'] ?>%</strong>
                        <span class="<?= $score_class ?>">(<?= $score_text ?>)</span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-2 text-center">
                    <div class="test-options">
                        <button class="btn btn-primary btn-test" onclick="startFullTest('<?= htmlspecialchars($cat['category']) ?>', <?= $cat['total_questions'] ?>, <?= $time_minutes * 60 ?>)">
                            <i class="fas fa-play"></i> Full Test
                        </button>
                        <button class="btn btn-outline-primary btn-test" onclick="startQuickTest('<?= htmlspecialchars($cat['category']) ?>')">
                            <i class="fas fa-bolt"></i> Quick (10)
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Topics in this category -->
            <div class="mt-3 pt-3 border-top">
                <small class="text-muted"><i class="fas fa-tags"></i> Topics:</small>
                <div>
                    <?php
                    // Reset topic pointer for this category
                    $topics_by_category->data_seek(0);
                    $topics_shown = 0;
                    while ($topic = $topics_by_category->fetch_assoc()) {
                        if ($topic['category'] == $cat['category'] && $topics_shown < 8) {
                            echo '<span class="topic-tag" onclick="startTopicTest(\'' . htmlspecialchars($cat['category']) . '\', \'' . htmlspecialchars($topic['topic']) . '\')">';
                            echo '<i class="fas fa-book"></i> ' . htmlspecialchars($topic['topic']) . ' (' . $topic['count'] . ')';
                            echo '</span>';
                            $topics_shown++;
                        }
                    }
                    ?>
                </div>
            </div>
            
            <!-- Performance Progress if user has taken tests -->
            <?php if ($user_perf && $user_perf['tests_taken'] > 0): ?>
            <div class="mt-3">
                <div class="d-flex justify-content-between small mb-1">
                    <span>Your Performance</span>
                    <span><?= $user_perf['avg_score'] ?>% average over <?= $user_perf['tests_taken'] ?> test(s)</span>
                </div>
                <div class="progress-custom">
                    <div class="progress-bar-custom" style="width: <?= $user_perf['avg_score'] ?>%; height: 8px;"></div>
                </div>
            </div>
            <?php else: ?>
            <div class="mt-3 text-muted small">
                <i class="fas fa-info-circle"></i> You haven't taken any tests in this category yet. Start now!
            </div>
            <?php endif; ?>
        </div>
        <?php $index++; endwhile; ?>
        
        <!-- Special Mixed Test Section -->
        <div class="category-card-large animate-fade-up" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white;">
            <div class="row align-items-center">
                <div class="col-md-2 col-3 text-center">
                    <div class="category-icon-large" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-bolt"></i>
                    </div>
                </div>
                <div class="col-md-5 col-9">
                    <h3 class="mb-1">Mixed Challenge Test</h3>
                    <p class="mb-0">Test your skills across ALL categories with random questions</p>
                </div>
                <div class="col-md-3">
                    <div><i class="fas fa-database"></i> <?= $total_questions_all ?> total questions</div>
                    <div><i class="fas fa-clock"></i> Estimated: <?= ceil($total_questions_all * 0.75 / 60) ?> hours</div>
                </div>
                <div class="col-md-2 text-center">
                    <button class="btn btn-light btn-test" onclick="startMixedTest(<?= $total_questions_all ?>)">
                        <i class="fas fa-play"></i> Start Mixed Test
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function startFullTest(category, totalQuestions, timeSeconds) {
            const minutes = Math.floor(timeSeconds / 60);
            const confirmMsg = `Start ${category} test?\n\n📊 Questions: ${totalQuestions}\n⏱️ Time: ${minutes} minutes\n🎯 Adaptive: Yes\n\nClick OK to begin your assessment.`;
            
            if (confirm(confirmMsg)) {
                window.location.href = `test.php?category=${encodeURIComponent(category)}`;
            }
        }
        
        function startQuickTest(category) {
            if (confirm(`Start Quick Test (10 questions) for ${category}?\n\n⏱️ Time: 10 minutes\n\nThis will give you a quick assessment of your skills.`)) {
                window.location.href = `test.php?category=${encodeURIComponent(category)}&quick=1`;
            }
        }
        
        function startTopicTest(category, topic) {
            if (confirm(`Start test on "${topic}"?\n\nCategory: ${category}\nTopic: ${topic}`)) {
                window.location.href = `test.php?category=${encodeURIComponent(category)}&topic=${encodeURIComponent(topic)}`;
            }
        }
        
        function startMixedTest(totalQuestions) {
            const hours = Math.ceil(totalQuestions * 0.75 / 60);
            if (confirm(`Start Mixed Challenge Test?\n\n📊 Questions: Adaptive (up to 30)\n🎯 Categories: All\n⏱️ Time: ~30 minutes\n\nThis will test your skills across all aptitude domains.`)) {
                window.location.href = `test.php`;
            }
        }
        
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