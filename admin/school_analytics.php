<?php include '../config/db.php'; ?>
<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// School performance
$school_performance = $conn->query("
    SELECT 
        COALESCE(u.school, 'Not Specified') as school,
        COUNT(DISTINCT u.id) as students,
        COUNT(r.id) as tests,
        ROUND(AVG(r.percentage), 1) as avg_score,
        SUM(CASE WHEN r.grade = 'A' THEN 1 ELSE 0 END) as a_count,
        SUM(CASE WHEN r.grade = 'B' THEN 1 ELSE 0 END) as b_count,
        SUM(CASE WHEN r.grade = 'C' THEN 1 ELSE 0 END) as c_count,
        SUM(CASE WHEN r.grade = 'D' THEN 1 ELSE 0 END) as d_count,
        SUM(CASE WHEN r.grade = 'F' THEN 1 ELSE 0 END) as f_count
    FROM users u
    LEFT JOIN results r ON u.id = r.user_id
    GROUP BY school
    ORDER BY avg_score DESC
");

// Regional performance
$region_performance = $conn->query("
    SELECT 
        COALESCE(u.region, 'Not Specified') as region,
        COUNT(DISTINCT u.id) as students,
        COUNT(r.id) as tests,
        ROUND(AVG(r.percentage), 1) as avg_score,
        COUNT(DISTINCT u.school) as schools
    FROM users u
    LEFT JOIN results r ON u.id = r.user_id
    GROUP BY region
    ORDER BY avg_score DESC
");

// Overall stats
$overall = $conn->query("
    SELECT 
        COUNT(DISTINCT u.id) as total_students,
        COUNT(r.id) as total_tests,
        ROUND(AVG(r.percentage), 1) as system_avg,
        COUNT(DISTINCT u.school) as total_schools,
        SUM(CASE WHEN r.grade = 'A' THEN 1 ELSE 0 END) as total_a
    FROM users u
    LEFT JOIN results r ON u.id = r.user_id
")->fetch_assoc();

// Monthly trend
$monthly_trend = $conn->query("
    SELECT 
        DATE_FORMAT(date, '%Y-%m') as month,
        COUNT(*) as tests,
        ROUND(AVG(percentage), 1) as avg_score
    FROM results
    WHERE date > DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(date, '%Y-%m')
    ORDER BY month DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Analytics - SATS Admin</title>
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
        .stat-number { font-size: 2rem; font-weight: bold; color: #003399; }
        .chart-container {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .school-rank-1 { background: #FFD700; color: #333; }
        .school-rank-2 { background: #C0C0C0; color: #333; }
        .school-rank-3 { background: #CD7F32; color: white; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-school"></i> School Performance Analytics</h2>
            <a href="dashboard.php" class="btn btn-secondary">Back to Admin</a>
        </div>
        
        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-school fa-2x text-primary mb-2"></i>
                    <div class="stat-number"><?= $overall['total_schools'] ?></div>
                    <div>Schools</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-users fa-2x text-success mb-2"></i>
                    <div class="stat-number"><?= $overall['total_students'] ?></div>
                    <div>Students</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-file-alt fa-2x text-info mb-2"></i>
                    <div class="stat-number"><?= $overall['total_tests'] ?></div>
                    <div>Tests Completed</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <i class="fas fa-percent fa-2x text-warning mb-2"></i>
                    <div class="stat-number"><?= $overall['system_avg'] ?>%</div>
                    <div>System Average</div>
                </div>
            </div>
        </div>
        
        <!-- School Ranking Table -->
        <div class="chart-container">
            <h5><i class="fas fa-trophy"></i> School Ranking</h5>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>School</th>
                            <th>Students</th>
                            <th>Tests</th>
                            <th>Avg Score</th>
                            <th>A</th>
                            <th>B</th>
                            <th>C</th>
                            <th>D</th>
                            <th>F</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $rank = 1;
                        while($school = $school_performance->fetch_assoc()): 
                            $rank_class = '';
                            if ($rank == 1) $rank_class = 'school-rank-1';
                            elseif ($rank == 2) $rank_class = 'school-rank-2';
                            elseif ($rank == 3) $rank_class = 'school-rank-3';
                        ?>
                        <tr>
                            <td class="<?= $rank_class ?> fw-bold">#<?= $rank++ ?></td>
                            <td><?= htmlspecialchars($school['school']) ?></td>
                            <td><?= $school['students'] ?></td>
                            <td><?= $school['tests'] ?></td>
                            <td><strong><?= $school['avg_score'] ?>%</strong></td>
                            <td class="text-success"><?= $school['a_count'] ?></td>
                            <td class="text-info"><?= $school['b_count'] ?></td>
                            <td class="text-warning"><?= $school['c_count'] ?></td>
                            <td class="text-secondary"><?= $school['d_count'] ?></td>
                            <td class="text-danger"><?= $school['f_count'] ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Regional Performance -->
        <div class="row">
            <div class="col-md-6">
                <div class="chart-container">
                    <h5><i class="fas fa-map-marker-alt"></i> Regional Performance</h5>
                    <canvas id="regionChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h5><i class="fas fa-chart-line"></i> 6-Month Trend</h5>
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Regional Table -->
        <div class="chart-container">
            <h5><i class="fas fa-table"></i> Regional Breakdown</h5>
            <table class="table">
                <thead>
                    <tr><th>Region</th><th>Schools</th><th>Students</th><th>Tests</th><th>Average Score</th></tr>
                </thead>
                <tbody>
                    <?php while($region = $region_performance->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($region['region']) ?></strong></td>
                        <td><?= $region['schools'] ?></td>
                        <td><?= $region['students'] ?></td>
                        <td><?= $region['tests'] ?></td>
                        <td><?= $region['avg_score'] ?>%</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Export Button -->
        <div class="text-center mb-4">
            <button onclick="exportData()" class="btn btn-primary">
                <i class="fas fa-download"></i> Export Report (CSV)
            </button>
        </div>
    </div>
    
    <script>
        // Regional Chart
        const regionCtx = document.getElementById('regionChart').getContext('2d');
        const regionData = <?php 
            $region_performance->data_seek(0);
            $regions = [];
            $scores = [];
            while($row = $region_performance->fetch_assoc()) {
                $regions[] = $row['region'];
                $scores[] = $row['avg_score'];
            }
            echo json_encode(['regions' => $regions, 'scores' => $scores]);
        ?>;
        
        new Chart(regionCtx, {
            type: 'bar',
            data: {
                labels: regionData.regions,
                datasets: [{
                    label: 'Average Score (%)',
                    data: regionData.scores,
                    backgroundColor: '#003399',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true, max: 100, title: { display: true, text: 'Score (%)' } } }
            }
        });
        
        // Trend Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        const trendData = <?php 
            $months = [];
            $trend_scores = [];
            while($row = $monthly_trend->fetch_assoc()) {
                $months[] = $row['month'];
                $trend_scores[] = $row['avg_score'];
            }
            echo json_encode(['months' => array_reverse($months), 'scores' => array_reverse($trend_scores)]);
        ?>;
        
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendData.months,
                datasets: [{
                    label: 'Average Score',
                    data: trendData.scores,
                    borderColor: '#CE9F32',
                    backgroundColor: 'rgba(206, 159, 50, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true, max: 100 } }
            }
        });
        
        function exportData() {
            window.location.href = 'export_school_report.php';
        }
    </script>
</body>
</html>