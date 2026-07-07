<?php include '../config/db.php'; ?>
<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all test results
$results = $conn->query("
    SELECT r.*, 
           (SELECT COUNT(*) FROM results WHERE user_id = r.user_id) as total_tests,
           (SELECT AVG(percentage) FROM results WHERE user_id = r.user_id) as overall_avg
    FROM results r 
    WHERE r.user_id = $user_id 
    ORDER BY r.date DESC
");

// Get domain summary
$domain_summary = $conn->query("
    SELECT 
        category,
        COUNT(*) as tests_taken,
        AVG(percentage) as avg_score,
        MAX(percentage) as best_score
    FROM results 
    WHERE user_id = $user_id
    GROUP BY category
");

$has_results = $results->num_rows > 0;
$overall_avg = $has_results ? $conn->query("SELECT AVG(percentage) as avg FROM results WHERE user_id = $user_id")->fetch_assoc()['avg'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Certificates - SATS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .certificate-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }
        .certificate-card:hover { transform: translateY(-3px); }
        .grade-badge {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
        }
        .grade-A { background: #28a745; }
        .grade-B { background: #17a2b8; }
        .grade-C { background: #ffc107; color: #333; }
        .grade-D { background: #fd7e14; }
        .grade-F { background: #dc3545; }
        .combined-card {
            background: linear-gradient(135deg, #003399, #001a66);
            color: white;
        }
        .combined-card .btn-outline-light {
            border-color: white;
            color: white;
        }
        .combined-card .btn-outline-light:hover {
            background: white;
            color: #003399;
        }
        .domain-badge {
            display: inline-block;
            background: #e9ecef;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            margin: 2px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-certificate"></i> My Certificates</h2>
            <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
        </div>
        
        <?php if($has_results): ?>
            <!-- Combined Certificate Card -->
            <div class="certificate-card combined-card">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="grade-badge" style="background: #CE9F32; color: #003399;">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <div class="col">
                        <h5 class="mb-1">🏆 COMBINED APTITUDE CERTIFICATE</h5>
                        <p class="mb-0 small opacity-75">
                            <i class="fas fa-chart-line"></i> Overall Score: <?= round($overall_avg, 1) ?>% | 
                            <?= $results->num_rows ?> Tests Completed
                        </p>
                        <div class="mt-2">
                            <?php 
                            $domain_summary->data_seek(0);
                            while($domain = $domain_summary->fetch_assoc()): 
                            ?>
                                <span class="domain-badge"><?= substr($domain['category'], 0, 20) ?>: <?= round($domain['avg_score'], 0) ?>%</span>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <a href="certificate.php" class="btn btn-outline-light">
                            <i class="fas fa-certificate"></i> View Combined Certificate
                        </a>
                    </div>
                </div>
            </div>
            
            <h5 class="mt-4 mb-3"><i class="fas fa-history"></i> Individual Test Certificates</h5>
            
            <?php 
            $results->data_seek(0);
            while($row = $results->fetch_assoc()): 
            ?>
            <div class="certificate-card">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="grade-badge grade-<?= $row['grade'] ?>"><?= $row['grade'] ?></div>
                    </div>
                    <div class="col">
                        <h5 class="mb-1"><?= htmlspecialchars($row['topic'] ?: $row['category']) ?></h5>
                        <p class="text-muted mb-0 small">
                            <i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($row['date'])) ?>
                            | Score: <?= $row['score'] ?>/<?= $row['total'] ?> (<?= round($row['percentage']) ?>%)
                            | Domain: <?= htmlspecialchars($row['category']) ?>
                        </p>
                    </div>
                    <div class="col-auto">
                        <a href="certificate.php?result=<?= $row['id'] ?>" class="btn btn-outline-primary">
                            <i class="fas fa-print"></i> View Certificate
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-certificate fa-4x text-muted mb-3"></i>
                <p>No certificates yet. Take a test to earn your first certificate!</p>
                <a href="dashboard.php" class="btn btn-primary">Start Test</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>