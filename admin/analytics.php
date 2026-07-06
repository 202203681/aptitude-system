<?php include '../config/db.php'; ?>
<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Get system-wide statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
$total_tests = $conn->query("SELECT COUNT(*) as count FROM results")->fetch_assoc()['count'];
$avg_score = $conn->query("SELECT AVG(percentage) as avg FROM results")->fetch_assoc()['avg'];

// Get daily activity for chart
$daily_activity = $conn->query("
    SELECT DATE(date) as day, COUNT(*) as tests 
    FROM results 
    WHERE date > DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(date)
    ORDER BY day
");

// Get category performance
$category_performance = $conn->query("
    SELECT category, AVG(percentage) as avg_score, COUNT(*) as tests
    FROM results
    GROUP BY category
");

// Get reliability metrics
$reliability = $conn->query("
    SELECT 
        AVG(percentage) as mean,
        STDDEV(percentage) as stddev,
        MIN(percentage) as min_score,
        MAX(percentage) as max_score
    FROM results
")->fetch_assoc();

// Get question performance for calibration
$question_performance = $conn->query("
    SELECT 
        q.id,
        q.category,
        q.question,
        q.difficulty,
        COUNT(r.id) as attempts,
        SUM(CASE WHEN r.is_correct THEN 1 ELSE 0 END) as correct,
        AVG(CASE WHEN r.is_correct THEN 1 ELSE 0 END) as p_value
    FROM questions q
    LEFT JOIN responses r ON q.id = r.question_id
    GROUP BY q.id
    HAVING attempts > 10
    ORDER BY p_value
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - SATS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #f0f2f5; }
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #003399;
        }
        .chart-container {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .reliability-card {
            background: linear-gradient(135deg, #003399, #001a66);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-chart-line"></i> SATS Analytics Dashboard</h2>
                    <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                    <div class="stat-number"><?= $total_users ?></div>
                    <div>Active Students</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-file-alt fa-2x text-success mb-2"></i>
                    <div class="stat-number"><?= $total_tests ?></div>
                    <div>Tests Completed</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-percent fa-2x text-warning mb-2"></i>
                    <div class="stat-number"><?= round($avg_score, 1) ?>%</div>
                    <div>Average Score</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card reliability-card">
                    <i class="fas fa-chart-simple fa-2x mb-2"></i>
                    <div class="stat-number"><?= round($reliability['mean'], 1) ?>%</div>
                    <div>System Mean</div>
                    <small>SD: ±<?= round($reliability['stddev'], 1) ?></small>
                </div>
            </div>
        </div>
        
        <!-- Reliability Metrics -->
        <div class="row">
            <div class="col-md-6">
                <div class="chart-container">
                    <h5><i class="fas fa-chart-bar"></i> Category Performance</h5>
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h5><i class="fas fa-chart-line"></i> 30-Day Activity</h5>
                    <canvas id="activityChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Item Calibration Table -->
        <div class="chart-container">
            <h5><i class="fas fa-calculator"></i> Item Calibration (IRT Parameters)</h5>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Item ID</th>
                            <th>Category</th>
                            <th>Question</th>
                            <th>Difficulty (b)</th>
                            <th>Attempts</th>
                            <th>p-value</th>
                            <th>Fit Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($item = $question_performance->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $item['id'] ?></td>
                            <td><?= substr($item['category'], 0, 20) ?></td>
                            <td><?= htmlspecialchars(substr($item['question'], 0, 40)) ?>...</td>
                            <td><?= number_format($item['difficulty'], 2) ?></td>
                            <td><?= $item['attempts'] ?></td>
                            <td><?= round($item['p_value'] * 100, 1) ?>%</td>
                            <td>
                                <?php if($item['p_value'] < 0.3): ?>
                                    <span class="badge bg-danger">Difficult</span>
                                <?php elseif($item['p_value'] > 0.8): ?>
                                    <span class="badge bg-success">Easy</span>
                                <?php else: ?>
                                    <span class="badge bg-primary">Good</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // Category Performance Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryData = <?php 
            $cats = [];
            $scores = [];
            while($row = $category_performance->fetch_assoc()) {
                $cats[] = $row['category'];
                $scores[] = round($row['avg_score'], 1);
            }
            echo json_encode(['categories' => $cats, 'scores' => $scores]);
        ?>;
        
        new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: categoryData.categories,
                datasets: [{
                    label: 'Average Score (%)',
                    data: categoryData.scores,
                    backgroundColor: '#003399',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: { display: true, text: 'Score (%)' }
                    }
                }
            }
        });
        
        // Activity Chart
        const activityCtx = document.getElementById('activityChart').getContext('2d');
        const activityData = <?php 
            $days = [];
            $counts = [];
            while($row = $daily_activity->fetch_assoc()) {
                $days[] = date('d M', strtotime($row['day']));
                $counts[] = $row['tests'];
            }
            echo json_encode(['days' => $days, 'counts' => $counts]);
        ?>;
        
        new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: activityData.days,
                datasets: [{
                    label: 'Tests per Day',
                    data: activityData.counts,
                    borderColor: '#CE9F32',
                    backgroundColor: 'rgba(206, 159, 50, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' }
                }
            }
        });
    </script>
</body>
</html>