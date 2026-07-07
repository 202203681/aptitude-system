<?php include '../config/db.php'; ?>
<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get strongest domain based on test performance
$strongest = $conn->query("
    SELECT category, AVG(percentage) as score, COUNT(*) as tests
    FROM results 
    WHERE user_id = $user_id
    GROUP BY category
    ORDER BY score DESC LIMIT 1
")->fetch_assoc();

// If no tests taken yet
if (!$strongest) {
    header("Location: dashboard.php?msg=take_test_first");
    exit();
}

$subject_map = [
    'Quantitative Aptitude' => [
        'core' => ['Mathematics', 'Accounting', 'Economics'],
        'electives' => ['Computer Science', 'Physics', 'Additional Mathematics', 'Business Studies'],
        'university_paths' => ['BCom (Accounting/Finance)', 'BSc Engineering', 'BSc Actuarial Science', 'BSc Data Science'],
        'careers' => ['Accountant', 'Data Analyst', 'Financial Manager', 'Statistician', 'Economist'],
        'min_score' => 65
    ],
    'Logical Reasoning' => [
        'core' => ['Computer Science', 'Mathematics', 'Physics'],
        'electives' => ['Design & Technology', 'Information Technology', 'Law', 'Psychology'],
        'university_paths' => ['BSc Computer Science', 'LLB Law', 'BSc IT', 'BSc Cybersecurity'],
        'careers' => ['Software Developer', 'Lawyer', 'IT Consultant', 'Cybersecurity Analyst', 'Researcher'],
        'min_score' => 60
    ],
    'Verbal Ability' => [
        'core' => ['English Literature', 'History', 'Religious Education'],
        'electives' => ['French', 'Journalism', 'Law', 'Sociology', 'Marketing'],
        'university_paths' => ['BA Humanities', 'LLB Law', 'Bachelor of Education', 'BA Communications'],
        'careers' => ['Journalist', 'Teacher', 'Lawyer', 'Content Writer', 'Public Relations Officer'],
        'min_score' => 60
    ]
];

$recommended = $subject_map[$strongest['category']] ?? $subject_map['Verbal Ability'];
$score_percentage = round($strongest['score']);

// Get all domain scores for comparison
$all_domains = $conn->query("
    SELECT category, AVG(percentage) as score
    FROM results WHERE user_id = $user_id
    GROUP BY category
    ORDER BY score DESC
");

// Save recommendation to database
$stmt = $conn->prepare("INSERT INTO subject_recommendations (user_id, category, recommended_subjects, university_paths, created_at) 
                         VALUES (?, ?, ?, ?, NOW())
                         ON DUPLICATE KEY UPDATE 
                         recommended_subjects = VALUES(recommended_subjects),
                         university_paths = VALUES(university_paths),
                         created_at = NOW()");
$subjects_json = json_encode($recommended['core']);
$paths_json = json_encode($recommended['university_paths']);
$stmt->bind_param("isss", $user_id, $strongest['category'], $subjects_json, $paths_json);
$stmt->execute();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Recommendations - SATS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%); }
        .recommendation-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .strength-badge {
            display: inline-block;
            background: linear-gradient(135deg, #003399, #001a66);
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: bold;
        }
        .subject-list { list-style: none; padding-left: 0; }
        .subject-list li {
            padding: 10px 15px;
            margin: 8px 0;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid #CE9F32;
        }
        .career-tag {
            display: inline-block;
            background: #e9ecef;
            padding: 6px 15px;
            border-radius: 20px;
            margin: 5px;
            font-size: 13px;
        }
        .print-btn { background: #003399; color: white; border: none; padding: 10px 25px; border-radius: 30px; }
        @media print {
            .no-print, .action-buttons, .navbar { display: none; }
            .recommendation-card { box-shadow: none; border: 1px solid #ddd; }
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <h2><i class="fas fa-graduation-cap"></i> Form 4 & 5 Subject Recommendations</h2>
            <div>
                <button onclick="window.print()" class="print-btn"><i class="fas fa-print"></i> Print</button>
                <a href="dashboard.php" class="btn btn-secondary ms-2">Back to Dashboard</a>
            </div>
        </div>
        
        <!-- Strength Card -->
        <div class="recommendation-card text-center">
            <i class="fas fa-chart-line fa-3x text-primary mb-3"></i>
            <h3>Your Strongest Aptitude Domain</h3>
            <div class="strength-badge mt-2 mb-3"><?= htmlspecialchars($strongest['category']) ?></div>
            <p>Score: <strong><?= $score_percentage ?>%</strong> (based on <?= $strongest['tests'] ?> test(s))</p>
        </div>
        
        <!-- Subject Recommendations -->
        <div class="recommendation-card">
            <h4><i class="fas fa-book-open"></i> Recommended Form 4 Subjects</h4>
            <div class="row">
                <div class="col-md-6">
                    <h5>Core Subjects:</h5>
                    <ul class="subject-list">
                        <?php foreach($recommended['core'] as $subject): ?>
                            <li><i class="fas fa-check-circle text-success me-2"></i> <?= $subject ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5>Recommended Electives:</h5>
                    <ul class="subject-list">
                        <?php foreach($recommended['electives'] as $subject): ?>
                            <li><i class="fas fa-star text-warning me-2"></i> <?= $subject ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Career Pathways -->
        <div class="recommendation-card">
            <h4><i class="fas fa-briefcase"></i> Career Pathways</h4>
            <div class="mb-3">
                <?php foreach($recommended['careers'] as $career): ?>
                    <span class="career-tag"><i class="fas fa-briefcase"></i> <?= $career ?></span>
                <?php endforeach; ?>
            </div>
            <h5 class="mt-3">University Pathways:</h5>
            <ul>
                <?php foreach($recommended['university_paths'] as $path): ?>
                    <li><?= $path ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- All Domains Performance -->
        <div class="recommendation-card">
            <h4><i class="fas fa-chart-pie"></i> Your Performance Across All Domains</h4>
            <table class="table">
                <thead>
                    <tr><th>Domain</th><th>Average Score</th><th>Proficiency</th><th>Recommended Focus</th></tr>
                </thead>
                <tbody>
                    <?php while($domain = $all_domains->fetch_assoc()): 
                        $score = round($domain['score']);
                        if ($score >= 70) $proficiency = 'Excellent';
                        elseif ($score >= 55) $proficiency = 'Good';
                        elseif ($score >= 40) $proficiency = 'Developing';
                        else $proficiency = 'Needs Improvement';
                        
                        $focus = ($domain['category'] == $strongest['category']) ? 'Your Strength - Build on it' : 'Consider extra practice';
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($domain['category']) ?></strong></td>
                        <td><?= $score ?>%</td>
                        <td><?= $proficiency ?></td>
                        <td><?= $focus ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Eswatini Specific Info -->
        <div class="recommendation-card bg-light">
            <h5><i class="fas fa-info-circle"></i> Important Information for Eswatini Students</h5>
            <ul>
                <li>Subject selections should be confirmed with your school's career guidance teacher</li>
                <li>Visit the <a href="https://www.moet.gov.sz" target="_blank">Ministry of Education website</a> for official curriculum information</li>
                <li>University of Eswatini (UNESWA) entry requirements vary by faculty</li>
                <li>Some careers may require additional subjects not listed - consult with your teachers</li>
            </ul>
        </div>
        
        <div class="text-center my-4 no-print">
            <a href="dashboard.php" class="btn btn-primary btn-lg">← Back to Dashboard</a>
            <a href="test.php" class="btn btn-success btn-lg ms-2"><i class="fas fa-play"></i> Take Another Test</a>
        </div>
    </div>
</body>
</html>