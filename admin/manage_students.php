<?php include '../config/db.php'; ?>
<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle student deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $student_id = intval($_GET['delete']);
    
    // Get student info before deletion for logging
    $student_info = $conn->query("SELECT first_name, last_name, student_id FROM users WHERE id = $student_id")->fetch_assoc();
    
    // Delete student (cascading delete will handle results, certificates, etc.)
    $delete_stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'user'");
    $delete_stmt->bind_param("i", $student_id);
    
    if ($delete_stmt->execute()) {
        $success_msg = "Student " . $student_info['first_name'] . " " . $student_info['last_name'] . " has been deleted successfully.";
        logSystemAction($conn, $_SESSION['user_id'], 'delete_student', ['student_id' => $student_id, 'name' => $student_info['first_name'] . ' ' . $student_info['last_name']]);
    } else {
        $error_msg = "Error deleting student: " . $conn->error;
    }
}

// Handle password reset
if (isset($_POST['reset_password']) && isset($_POST['student_id'])) {
    $student_id = intval($_POST['student_id']);
    $new_password = 'Student123';
    
    $reset_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'user'");
    $reset_stmt->bind_param("si", $new_password, $student_id);
    
    if ($reset_stmt->execute()) {
        $success_msg = "Password reset to 'Student123' for student ID: " . $student_id;
        logSystemAction($conn, $_SESSION['user_id'], 'reset_password', ['student_id' => $student_id]);
    } else {
        $error_msg = "Error resetting password";
    }
}

// Get all students with their statistics
$students = $conn->query("
    SELECT 
        u.*,
        COUNT(DISTINCT r.id) as total_tests,
        ROUND(AVG(r.percentage), 1) as avg_score,
        MAX(r.percentage) as best_score,
        COUNT(CASE WHEN r.grade = 'A' THEN 1 END) as a_count,
        COUNT(CASE WHEN r.grade = 'B' THEN 1 END) as b_count,
        COUNT(CASE WHEN r.grade = 'C' THEN 1 END) as c_count,
        (SELECT COUNT(*) FROM certificates c WHERE c.result_id IN (SELECT id FROM results WHERE user_id = u.id)) as certificate_count
    FROM users u
    LEFT JOIN results r ON u.id = r.user_id
    WHERE u.role = 'user'
    GROUP BY u.id
    ORDER BY u.created_at DESC
");

// Get search parameter
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
if ($search) {
    $students = $conn->query("
        SELECT 
            u.*,
            COUNT(DISTINCT r.id) as total_tests,
            ROUND(AVG(r.percentage), 1) as avg_score,
            MAX(r.percentage) as best_score,
            COUNT(CASE WHEN r.grade = 'A' THEN 1 END) as a_count,
            COUNT(CASE WHEN r.grade = 'B' THEN 1 END) as b_count,
            COUNT(CASE WHEN r.grade = 'C' THEN 1 END) as c_count,
            (SELECT COUNT(*) FROM certificates c WHERE c.result_id IN (SELECT id FROM results WHERE user_id = u.id)) as certificate_count
        FROM users u
        LEFT JOIN results r ON u.id = r.user_id
        WHERE u.role = 'user' 
        AND (u.first_name LIKE '%$search%' 
            OR u.last_name LIKE '%$search%' 
            OR u.student_id LIKE '%$search%' 
            OR u.email LIKE '%$search%'
            OR u.school LIKE '%$search%')
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
}

// Get statistics
$total_students = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'")->fetch_assoc()['total'];
$total_tests = $conn->query("SELECT COUNT(*) as total FROM results")->fetch_assoc()['total'];
$total_certificates = $conn->query("SELECT COUNT(*) as total FROM certificates")->fetch_assoc()['total'];
$avg_overall = $conn->query("SELECT ROUND(AVG(percentage), 1) as avg FROM results")->fetch_assoc()['avg'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - SATS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        body { background: #f0f2f5; }
        
        .stat-card {
            background: var(--card-bg, white);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary, #003399);
        }
        
        .student-card {
            background: var(--card-bg, white);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .student-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .student-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .btn-action {
            margin: 2px;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 12px;
        }
        
        .certificate-preview {
            max-width: 100%;
            border-radius: 10px;
            border: 1px solid #ddd;
        }
        
        .modal-xl {
            max-width: 1000px;
        }
        
        .certificate-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .certificate-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .certificate-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .filter-bar {
            background: var(--card-bg, white);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .grade-A { color: #28a745; font-weight: bold; }
        .grade-B { color: #17a2b8; font-weight: bold; }
        .grade-C { color: #ffc107; font-weight: bold; }
        .grade-D { color: #fd7e14; font-weight: bold; }
        .grade-F { color: #dc3545; font-weight: bold; }
        
        @media (max-width: 768px) {
            .student-avatar { width: 40px; height: 40px; font-size: 1rem; }
            .btn-action { padding: 3px 8px; font-size: 10px; }
        }
    </style>
</head>
<body data-theme="mixed">
    <div class="container mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-users"></i> Manage Students</h2>
            <div>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="../auth/logout.php" class="btn btn-danger ms-2" onclick="return confirm('Logout?')">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= $total_students ?></div>
                    <div><i class="fas fa-users"></i> Total Students</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= $total_tests ?></div>
                    <div><i class="fas fa-file-alt"></i> Total Tests</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= $total_certificates ?></div>
                    <div><i class="fas fa-certificate"></i> Certificates</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= $avg_overall ?? 0 ?>%</div>
                    <div><i class="fas fa-chart-line"></i> Avg Score</div>
                </div>
            </div>
        </div>
        
        <!-- Search and Filter Bar -->
        <div class="filter-bar">
            <form method="GET" class="row">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by name, student ID, email, or school..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>
        
        <!-- Students List -->
        <div class="row">
            <div class="col-12">
                <?php if (isset($success_msg)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> <?= $success_msg ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?= $error_msg ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($students && $students->num_rows > 0): ?>
                    <?php while ($student = $students->fetch_assoc()): ?>
                        <div class="student-card" id="student-<?= $student['id'] ?>">
                            <div class="row align-items-center">
                                <div class="col-md-2 col-3">
                                    <div class="student-avatar mx-auto">
                                        <?php if (!empty($student['profile_picture'])): ?>
                                            <img src="../uploads/profiles/<?= $student['profile_picture'] ?>" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                                        <?php else: ?>
                                            <i class="fas fa-user-graduate"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-4 col-9">
                                    <h5 class="mb-1"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></h5>
                                    <small class="text-muted">
                                        <i class="fas fa-id-card"></i> <?= htmlspecialchars($student['student_id']) ?><br>
                                        <i class="fas fa-envelope"></i> <?= htmlspecialchars($student['email']) ?>
                                    </small>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-1">
                                        <i class="fas fa-school"></i> <?= htmlspecialchars($student['school'] ?: 'N/A') ?>
                                    </div>
                                    <div class="mb-1">
                                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($student['region'] ?: 'N/A') ?>
                                    </div>
                                    <div>
                                        <i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($student['created_at'])) ?>
                                    </div>
                                </div>
                                <div class="col-md-3 text-md-end">
                                    <div class="mb-2">
                                        <span class="badge bg-primary">📊 <?= $student['total_tests'] ?> Tests</span>
                                        <span class="badge bg-success">⭐ <?= $student['avg_score'] ?>%</span>
                                        <span class="badge bg-info">🏆 <?= $student['a_count'] ?> A's</span>
                                        <span class="badge bg-warning">📜 <?= $student['certificate_count'] ?> Certs</span>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-info btn-action" onclick="viewStudentDetails(<?= $student['id'] ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button class="btn btn-sm btn-primary btn-action" onclick="viewCertificates(<?= $student['id'] ?>, '<?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>')">
                                            <i class="fas fa-certificate"></i> Certs
                                        </button>
                                        <button class="btn btn-sm btn-warning btn-action" onclick="resetPassword(<?= $student['id'] ?>)">
                                            <i class="fas fa-key"></i> Reset PW
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-action" onclick="deleteStudent(<?= $student['id'] ?>, '<?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-4x text-muted mb-3"></i>
                        <p>No students found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Student Details Modal -->
    <div class="modal fade" id="studentDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white;">
                    <h5 class="modal-title"><i class="fas fa-user-graduate"></i> Student Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="studentDetailsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary"></div>
                        <p>Loading...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Certificates Modal -->
    <div class="modal fade" id="certificatesModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white;">
                    <h5 class="modal-title"><i class="fas fa-certificate"></i> Student Certificates</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="certificatesContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary"></div>
                        <p>Loading certificates...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Certificate View Modal -->
    <div class="modal fade" id="certificateViewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white;">
                    <h5 class="modal-title"><i class="fas fa-certificate"></i> Certificate Preview</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="certificateViewContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary"></div>
                        <p>Loading certificate...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="printCertificate()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewStudentDetails(studentId) {
            const modal = new bootstrap.Modal(document.getElementById('studentDetailsModal'));
            document.getElementById('studentDetailsContent').innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p>Loading student details...</p>
                </div>
            `;
            modal.show();
            
            fetch(`get_student_details.php?id=${studentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('studentDetailsContent').innerHTML = `
                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <div class="student-avatar mx-auto mb-3" style="width: 100px; height: 100px; font-size: 3rem;">
                                        ${data.profile_picture ? 
                                            `<img src="../uploads/profiles/${data.profile_picture}" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">` : 
                                            '<i class="fas fa-user-graduate"></i>'}
                                    </div>
                                    <h4>${data.first_name} ${data.last_name}</h4>
                                    <p class="text-muted">${data.student_id}</p>
                                </div>
                                <div class="col-md-8">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <strong><i class="fas fa-envelope"></i> Email:</strong><br>${data.email}
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong><i class="fas fa-school"></i> School:</strong><br>${data.school || 'Not specified'}
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong><i class="fas fa-map-marker-alt"></i> Region:</strong><br>${data.region || 'Not specified'}
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong><i class="fas fa-calendar"></i> Year:</strong><br>${data.year || 'Not specified'}
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong><i class="fas fa-trophy"></i> Total Tests:</strong><br>${data.total_tests}
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong><i class="fas fa-chart-line"></i> Average Score:</strong><br>${data.avg_score}%
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong><i class="fas fa-star"></i> Best Score:</strong><br>${data.best_score}%
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong><i class="fas fa-certificate"></i> Certificates:</strong><br>${data.certificate_count}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <h6>Recent Test Results</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr><th>Date</th><th>Category</th><th>Score</th><th>Grade</th></tr>
                                    </thead>
                                    <tbody>
                                        ${data.recent_results.map(r => `
                                            <tr>
                                                <td>${new Date(r.date).toLocaleDateString()}</td>
                                                <td>${r.category}</td>
                                                <td>${r.score}/${r.total} (${r.percentage}%)</td>
                                                <td><span class="grade-${r.grade}">${r.grade}</span></td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    } else {
                        document.getElementById('studentDetailsContent').innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    }
                });
        }
        
        function viewCertificates(studentId, studentName) {
            const modal = new bootstrap.Modal(document.getElementById('certificatesModal'));
            document.getElementById('certificatesContent').innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p>Loading certificates for ${studentName}...</p>
                </div>
            `;
            modal.show();
            
            fetch(`get_student_certificates.php?id=${studentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.certificates.length > 0) {
                        document.getElementById('certificatesContent').innerHTML = `
                            <div class="certificate-list">
                                ${data.certificates.map(cert => `
                                    <div class="certificate-item" onclick="viewCertificate(${cert.id})">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <strong><i class="fas fa-certificate text-primary"></i> ${cert.certificate_code}</strong><br>
                                                <small>Category: ${cert.category}</small><br>
                                                <small>Score: ${cert.score}/${cert.total} (${cert.percentage}%) - Grade: ${cert.grade}</small>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <small>Issued: ${new Date(cert.issued_date).toLocaleDateString()}</small><br>
                                                <button class="btn btn-sm btn-primary mt-2" onclick="event.stopPropagation(); viewCertificate(${cert.id})">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                <button class="btn btn-sm btn-success mt-2" onclick="event.stopPropagation(); downloadCertificate(${cert.id})">
                                                    <i class="fas fa-download"></i> Download
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        `;
                    } else {
                        document.getElementById('certificatesContent').innerHTML = `<div class="alert alert-info">No certificates found for this student.</div>`;
                    }
                });
        }
        
        function viewCertificate(certificateId) {
            const modal = new bootstrap.Modal(document.getElementById('certificateViewModal'));
            document.getElementById('certificateViewContent').innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p>Loading certificate...</p>
                </div>
            `;
            modal.show();
            
            window.open(`view_certificate.php?id=${certificateId}`, '_blank');
        }
        
        function printCertificate() {
            window.print();
        }
        
        function downloadCertificate(certificateId) {
            window.open(`download_certificate.php?id=${certificateId}`, '_blank');
        }
        
        function resetPassword(studentId) {
            if (confirm('Reset password to default "Student123" for this student?')) {
                const formData = new FormData();
                formData.append('reset_password', '1');
                formData.append('student_id', studentId);
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                }).then(() => {
                    location.reload();
                });
            }
        }
        
        function deleteStudent(studentId, studentName) {
            if (confirm(`Are you sure you want to delete ${studentName}? This will permanently remove all their test results and certificates.`)) {
                if (confirm(`LAST WARNING: This action cannot be undone! Type "DELETE" to confirm.`)) {
                    const confirmation = prompt(`Type "DELETE" to permanently remove ${studentName}`);
                    if (confirmation === 'DELETE') {
                        window.location.href = `?delete=${studentId}`;
                    } else {
                        alert('Deletion cancelled. Confirmation text did not match.');
                    }
                }
            }
        }
    </script>
</body>
</html>