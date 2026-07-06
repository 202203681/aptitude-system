<?php include '../config/db.php'; ?>
<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Active tests
$active_tests = $conn->query("
    SELECT t.id, t.start_time, u.id as user_id, u.first_name, u.last_name, u.school,
           TIMESTAMPDIFF(MINUTE, t.start_time, NOW()) as minutes_elapsed,
           (SELECT COUNT(*) FROM responses r WHERE r.test_id = t.id) as answers_given
    FROM tests t
    JOIN users u ON t.user_id = u.id
    WHERE t.end_time IS NULL AND t.start_time > DATE_SUB(NOW(), INTERVAL 2 HOUR)
    ORDER BY t.start_time DESC
");

// Suspicious events
$suspicious = $conn->query("
    SELECT l.*, u.first_name, u.last_name, u.school
    FROM system_logs l
    JOIN users u ON l.user_id = u.id
    WHERE l.action IN ('tab_switch', 'copy_attempt', 'paste_attempt', 'right_click', 'stress_metric')
    AND l.timestamp > DATE_SUB(NOW(), INTERVAL 1 DAY)
    ORDER BY l.timestamp DESC
    LIMIT 100
");

// Cheating summary by student
$cheating_summary = $conn->query("
    SELECT 
        u.id, u.first_name, u.last_name, u.school,
        COUNT(CASE WHEN l.action = 'tab_switch' THEN 1 END) as tab_switches,
        COUNT(CASE WHEN l.action = 'copy_attempt' THEN 1 END) as copy_attempts,
        COUNT(CASE WHEN l.action = 'paste_attempt' THEN 1 END) as paste_attempts
    FROM users u
    LEFT JOIN system_logs l ON u.id = l.user_id AND l.timestamp > DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY u.id
    HAVING tab_switches > 0 OR copy_attempts > 0 OR paste_attempts > 0
    ORDER BY tab_switches DESC
    LIMIT 50
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Proctoring - SATS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f0f2f5; }
        .alert-suspicious { background: #fff3cd; border-left: 4px solid #ffc107; }
        .alert-critical { background: #f8d7da; border-left: 4px solid #dc3545; }
        .refresh-btn { position: fixed; bottom: 20px; right: 20px; z-index: 1000; }
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .stat-number { font-size: 2rem; font-weight: bold; color: #003399; }
        .card-header {
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .stat-number { font-size: 1.5rem; }
        }
    </style>
    <meta http-equiv="refresh" content="30">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-eye"></i> Live Proctoring Dashboard</h2>
            <div>
                <a href="dashboard.php" class="btn btn-secondary">Back</a>
                <button onclick="location.reload()" class="btn btn-primary"><i class="fas fa-sync-alt"></i> Refresh</button>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= $active_tests->num_rows ?></div>
                    <div><i class="fas fa-play-circle"></i> Active Tests</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= $suspicious->num_rows ?></div>
                    <div><i class="fas fa-exclamation-triangle"></i> Suspicious Events</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= $cheating_summary->num_rows ?></div>
                    <div><i class="fas fa-user-slash"></i> Flagged Students</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <?php
                        $total_events = $conn->query("SELECT COUNT(*) as total FROM system_logs WHERE action IN ('tab_switch', 'copy_attempt', 'paste_attempt', 'right_click') AND timestamp > DATE_SUB(NOW(), INTERVAL 1 DAY)")->fetch_assoc()['total'];
                        echo $total_events;
                        ?>
                    </div>
                    <div><i class="fas fa-list"></i> Total Events (24h)</div>
                </div>
            </div>
        </div>
        
        <!-- Active Tests -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-play-circle"></i> Active Tests (<?= $active_tests->num_rows ?>)
            </div>
            <div class="card-body">
                <?php if($active_tests->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>School</th>
                                <th>Duration</th>
                                <th>Progress</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($test = $active_tests->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($test['first_name'] . ' ' . $test['last_name']) ?></td>
                                <td><?= htmlspecialchars($test['school'] ?: 'N/A') ?></td>
                                <td><?= $test['minutes_elapsed'] ?> min</td>
                                <td><?= $test['answers_given'] ?> answered</td>
                                <td><span class="badge bg-success"><i class="fas fa-circle"></i> Active</span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">No active tests at this time.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Cheating Summary -->
        <div class="card mb-4">
            <div class="card-header bg-warning">
                <i class="fas fa-exclamation-triangle"></i> Students with Suspicious Activity (Last 7 Days)
            </div>
            <div class="card-body">
                <?php if($cheating_summary->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>School</th>
                                <th>Tab Switches</th>
                                <th>Copy Attempts</th>
                                <th>Paste Attempts</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($student = $cheating_summary->fetch_assoc()): ?>
                            <tr class="<?= $student['tab_switches'] > 5 ? 'table-danger' : 'table-warning' ?>">
                                <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td>
                                <td><?= htmlspecialchars($student['school'] ?: 'N/A') ?></td>
                                <td><span class="badge bg-danger"><?= $student['tab_switches'] ?></span></td>
                                <td><span class="badge bg-warning"><?= $student['copy_attempts'] ?></span></td>
                                <td><span class="badge bg-info"><?= $student['paste_attempts'] ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-danger" onclick="warnStudent(<?= $student['id'] ?>)">
                                        <i class="fas fa-envelope"></i> Warn
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-success text-center"><i class="fas fa-check-circle"></i> No suspicious activity detected.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Suspicious Events -->
        <div class="card">
            <div class="card-header bg-danger text-white">
                <i class="fas fa-list"></i> Recent Suspicious Events (Last 24 Hours)
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Student</th>
                                <th>School</th>
                                <th>Event Type</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($event = $suspicious->fetch_assoc()): 
                                $event_class = '';
                                if ($event['action'] == 'tab_switch') {
                                    $event_class = 'alert-suspicious';
                                } elseif ($event['action'] == 'stress_metric') {
                                    $event_class = 'alert-info';
                                } else {
                                    $event_class = 'alert-critical';
                                }
                            ?>
                            <tr class="<?= $event_class ?>">
                                <td><?= date('d M H:i:s', strtotime($event['timestamp'])) ?></td>
                                <td><?= htmlspecialchars($event['first_name'] . ' ' . $event['last_name']) ?></td>
                                <td><?= htmlspecialchars($event['school'] ?: 'N/A') ?></td>
                                <td>
                                    <span class="badge <?= 
                                        $event['action'] == 'tab_switch' ? 'bg-warning' : 
                                        ($event['action'] == 'stress_metric' ? 'bg-info' : 
                                        'bg-danger') 
                                    ?>">
                                        <?= str_replace('_', ' ', ucfirst($event['action'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $details = json_decode($event['details'], true);
                                    if ($details) {
                                        echo htmlspecialchars(implode(', ', $details));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function warnStudent(studentId) {
            if (confirm('Send a warning notification to this student?')) {
                fetch('../api/warn_student.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ student_id: studentId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Warning sent successfully!');
                    } else {
                        alert('Error sending warning: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error: ' + error);
                });
            }
        }
        
        // Auto-refresh every 30 seconds (already set via meta refresh)
        // But also allow manual refresh with button
    </script>
</body>
</html>