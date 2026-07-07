<?php include '../config/db.php'; ?>
<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$test_id = isset($_POST['test_id']) ? intval($_POST['test_id']) : 0;

// Calculate score
$score = 0;
$total = 0;

foreach ($_POST as $key => $value) {
    if (strpos($key, 'answer_') === 0) {
        $total++;
        $qid = str_replace('answer_', '', $key);
        $correct_key = 'correct_' . $qid;
        
        if (isset($_POST[$correct_key]) && $value == $_POST[$correct_key]) {
            $score++;
        }
    }
}

$percentage = ($total > 0) ? ($score / $total) * 100 : 0;

// Determine grade
if ($percentage >= 80) $grade = 'A';
elseif ($percentage >= 70) $grade = 'B';
elseif ($percentage >= 60) $grade = 'C';
elseif ($percentage >= 50) $grade = 'D';
else $grade = 'F';

// Calculate scaled score
$scaled_score = 50 + (($percentage - 50) / 20);
$percentile_rank = min(99, max(1, round(50 + (($percentage - 50) / 2))));

// Save result
$category = isset($_POST['category']) ? $_POST['category'] : 'Mixed';
$topic = isset($_POST['topic']) ? $_POST['topic'] : 'General';

$stmt = $conn->prepare("INSERT INTO results (user_id, test_id, category, topic, score, total, percentage, scaled_score, percentile_rank, grade) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iissiiidii", $user_id, $test_id, $category, $topic, $score, $total, $percentage, $scaled_score, $percentile_rank, $grade);
$stmt->execute();
$result_id = $conn->insert_id;

// Get user info
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results - Smart Aptitude Testing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .result-card {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            border-radius: 24px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .score-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #003399, #CE9F32);
            margin: 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
        }
        .grade {
            font-size: 3rem;
            font-weight: bold;
        }
        .grade-A { color: #28a745; }
        .grade-B { color: #17a2b8; }
        .grade-C { color: #ffc107; }
        .grade-D { color: #fd7e14; }
        .grade-F { color: #dc3545; }
        .btn-certificate {
            background: linear-gradient(135deg, #CE9F32, #b8860b);
            border: none;
            padding: 12px 30px;
            font-weight: bold;
        }
        .btn-certificate:hover {
            transform: translateY(-2px);
        }
        .recommendation-box {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 20px;
            margin-top: 25px;
            text-align: left;
        }
        .recommendation-tag {
            display: inline-block;
            background: #e9ecef;
            padding: 5px 12px;
            border-radius: 20px;
            margin: 5px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="result-card">
        <i class="fas fa-chart-line fa-3x text-primary mb-3"></i>
        <h2>Test Results</h2>
        
        <div class="score-circle">
            <?= round($percentage) ?>%
        </div>
        
        <div class="grade grade-<?= $grade ?>">
            Grade: <?= $grade ?>
        </div>
        
        <div class="mt-3">
            <h3><?= $score ?> / <?= $total ?></h3>
            <p class="text-muted">Correct Answers</p>
        </div>
        
        <div class="row mt-4">
            <div class="col-6">
                <div class="border rounded p-2">
                    <small class="text-muted">Scaled Score</small>
                    <h4><?= round($scaled_score) ?></h4>
                </div>
            </div>
            <div class="col-6">
                <div class="border rounded p-2">
                    <small class="text-muted">Percentile Rank</small>
                    <h4><?= $percentile_rank ?>th</h4>
                </div>
            </div>
        </div>
        
        <!-- Career Recommendations -->
        <div class="recommendation-box">
            <h6><i class="fas fa-briefcase"></i> Recommended Career Pathways</h6>
            <div>
                <?php
                $careers = [];
                if ($category == 'Quantitative Aptitude') {
                    if ($percentage >= 70) $careers = ['Data Scientist', 'Actuary', 'Financial Analyst', 'Software Engineer', 'Statistician'];
                    elseif ($percentage >= 50) $careers = ['Accountant', 'Banker', 'Business Analyst', 'Economist'];
                    else $careers = ['Retail Manager', 'Administrator', 'Sales Associate'];
                } elseif ($category == 'Logical Reasoning') {
                    if ($percentage >= 70) $careers = ['Lawyer', 'Judge', 'Detective', 'IT Security Analyst', 'Researcher'];
                    elseif ($percentage >= 50) $careers = ['Police Officer', 'Paralegal', 'Programmer', 'Manager'];
                    else $careers = ['Customer Support', 'Administrative Assistant', 'Sales'];
                } else {
                    if ($percentage >= 70) $careers = ['Journalist', 'Content Writer', 'Translator', 'Public Relations Specialist', 'Lawyer'];
                    elseif ($percentage >= 50) $careers = ['Teacher', 'Editor', 'Marketing Specialist', 'Copywriter'];
                    else $careers = ['Receptionist', 'Call Center Agent', 'Administrative Assistant'];
                }
                foreach ($careers as $career): ?>
                    <span class="recommendation-tag"><i class="fas fa-briefcase"></i> <?= $career ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="certificate.php?result=<?= $result_id ?>" class="btn btn-certificate btn-lg">
                <i class="fas fa-certificate"></i> View Certificate
            </a>
            <a href="dashboard.php" class="btn btn-outline-secondary btn-lg mt-2 mt-sm-0">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </div>
    </div>
</body>
</html>