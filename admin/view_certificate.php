<?php include '../config/db.php'; ?>
<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if (!$user_id) {
    header("Location: view_results.php");
    exit();
}

// Get user data
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
if (!$user) {
    header("Location: view_results.php");
    exit();
}

// Get all results for this user
$results = $conn->query("SELECT * FROM results WHERE user_id = $user_id ORDER BY date DESC");

// Calculate overall performance
$overall = $conn->query("
    SELECT 
        COUNT(*) as total_tests,
        AVG(percentage) as avg_percentage,
        AVG(score) as avg_score,
        AVG(total) as avg_total,
        MAX(percentage) as best_score
    FROM results 
    WHERE user_id = $user_id
")->fetch_assoc();

// Get domain performance
$domain_performance = $conn->query("
    SELECT 
        category,
        COUNT(*) as tests_taken,
        AVG(percentage) as avg_score,
        MAX(percentage) as best_score,
        AVG(score) as avg_correct,
        AVG(total) as avg_total
    FROM results 
    WHERE user_id = $user_id
    GROUP BY category
    ORDER BY avg_score DESC
");

// Calculate overall grade
$overall_percentage = $overall['avg_percentage'];
if ($overall_percentage >= 80) $overall_grade = 'A';
elseif ($overall_percentage >= 70) $overall_grade = 'B';
elseif ($overall_percentage >= 60) $overall_grade = 'C';
elseif ($overall_percentage >= 50) $overall_grade = 'D';
else $overall_grade = 'F';

// Generate certificate ID
$certificate_id = "SATS-ADMIN-" . date('Y') . "-" . str_pad($user_id, 6, '0', STR_PAD_LEFT);

// Generate QR Code
$verification_url = "http://" . $_SERVER['HTTP_HOST'] . "/aptitude-system/verify_certificate.php?cert_id=" . urlencode($certificate_id);
$qr_url = "https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=" . urlencode($verification_url) . "&choe=UTF-8";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Certificate - SATS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700&display=swap');
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: linear-gradient(135deg, #1a472a 0%, #0f2a1a 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        .certificate-container { max-width: 1100px; width: 100%; margin: 0 auto; }
        
        .certificate {
            background: #fffef7;
            padding: 50px 55px;
            position: relative;
            box-shadow: 0 30px 60px rgba(0,0,0,0.3);
            border-radius: 4px;
        }
        
        .certificate-border {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            border: 2px solid #CE9F32;
            border-radius: 8px;
            pointer-events: none;
        }
        
        .corner {
            position: absolute;
            width: 50px;
            height: 50px;
        }
        .corner-tl { top: 30px; left: 30px; border-top: 3px solid #003399; border-left: 3px solid #003399; }
        .corner-tr { top: 30px; right: 30px; border-top: 3px solid #003399; border-right: 3px solid #003399; }
        .corner-bl { bottom: 30px; left: 30px; border-bottom: 3px solid #003399; border-left: 3px solid #003399; }
        .corner-br { bottom: 30px; right: 30px; border-bottom: 3px solid #003399; border-right: 3px solid #003399; }
        
        .certificate-header { text-align: center; margin-bottom: 35px; position: relative; }
        
        .logo-section {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-bottom: 20px;
        }
        
        .logo {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #003399, #001a66);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
        }
        
        .title-main {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 800;
            color: #003399;
            letter-spacing: 2px;
            margin-bottom: 8px;
        }
        
        .subtitle {
            font-size: 14px;
            color: #CE9F32;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .ministry-names {
            font-size: 11px;
            color: #888;
            margin-top: 10px;
        }
        
        .student-details {
            background: linear-gradient(135deg, #f8f9fa, #fff);
            border-radius: 12px;
            padding: 20px 25px;
            margin: 20px 0;
            border-left: 4px solid #CE9F32;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }
        
        .detail-item { display: flex; flex-direction: column; }
        .detail-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #888; margin-bottom: 5px; }
        .detail-value { font-size: 15px; font-weight: 600; color: #1a2a3a; }
        
        .overall-score {
            background: linear-gradient(135deg, #003399, #001a66);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        
        .overall-percentage { font-size: 48px; font-weight: 800; }
        .overall-grade { font-size: 36px; font-weight: 800; margin-top: 10px; }
        .proficiency { font-size: 18px; margin-top: 10px; color: #CE9F32; }
        
        .domains-taken {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px 20px;
            margin: 20px 0;
            border-left: 4px solid #CE9F32;
        }
        
        .domains-taken .label {
            font-size: 12px;
            font-weight: 700;
            color: #003399;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .domains-taken .domains-text {
            font-size: 14px;
            color: #333;
            margin-top: 5px;
        }
        
        .career-section {
            margin: 20px 0;
            padding: 20px;
            background: #f0f4f0;
            border-radius: 12px;
        }
        
        .career-title {
            font-size: 14px;
            font-weight: 700;
            color: #003399;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .career-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .career-tag {
            background: white;
            padding: 6px 15px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 500;
            color: #1a472a;
            border: 1px solid #CE9F32;
        }
        
        .certificate-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px dashed #ddd;
        }
        
        .signature-area { text-align: center; }
        .signature-line { width: 150px; border-bottom: 1px solid #333; margin-bottom: 8px; }
        .signature-name { font-size: 11px; font-weight: 600; }
        .signature-title { font-size: 9px; color: #888; }
        
        .stamp { text-align: center; }
        .stamp-icon {
            width: 60px;
            height: 60px;
            border: 2px solid #CE9F32;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
            color: #CE9F32;
            font-size: 20px;
        }
        
        .certificate-id { font-size: 9px; color: #999; font-family: monospace; }
        
        .qr-code { text-align: center; }
        .qr-image {
            width: 80px;
            height: 80px;
            border: 1px solid #ddd;
            padding: 5px;
            background: white;
        }
        .qr-label { font-size: 8px; color: #666; margin-top: 5px; }
        
        .admin-badge {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-left: 10px;
        }
        
        @media print {
            body { background: white; padding: 0; margin: 0; }
            .certificate { box-shadow: none; padding: 30px; }
            .no-print { display: none; }
        }
        
        @media (max-width: 768px) {
            .certificate { padding: 30px 20px; }
            .details-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .signature-line { width: 100px; }
        }
        
        .action-buttons { margin-top: 30px; text-align: center; }
        .btn-print { background: linear-gradient(135deg, #003399, #001a66); border: none; padding: 12px 32px; font-weight: 600; margin: 0 10px; border-radius: 40px; color: white; }
        .btn-back { background: #6c757d; border: none; padding: 12px 32px; font-weight: 600; margin: 0 10px; border-radius: 40px; color: white; }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="certificate">
            <div class="certificate-border"></div>
            <div class="corner corner-tl"></div>
            <div class="corner corner-tr"></div>
            <div class="corner corner-bl"></div>
            <div class="corner corner-br"></div>
            
            <div class="certificate-header">
                <div class="logo-section">
                    <div class="logo"><i class="fas fa-graduation-cap"></i></div>
                    <div class="logo"><i class="fas fa-brain"></i></div>
                    <div class="logo"><i class="fas fa-briefcase"></i></div>
                </div>
                <div class="subtitle">KINGDOM OF ESWATINI</div>
                <div class="title-main">
                    COMBINED APTITUDE CERTIFICATE
                    <span class="admin-badge">Admin View</span>
                </div>
                <div class="ministry-names">
                    Ministry of Labour & Social Security | Ministry of Education & Training
                </div>
            </div>
            
            <div class="student-details">
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label"><i class="fas fa-user"></i> Full Name</div>
                        <div class="detail-value"><?= htmlspecialchars(strtoupper($user['first_name'] . ' ' . $user['last_name'])) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label"><i class="fas fa-id-card"></i> Student ID</div>
                        <div class="detail-value"><?= htmlspecialchars($user['student_id']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label"><i class="fas fa-school"></i> School</div>
                        <div class="detail-value"><?= htmlspecialchars($user['school'] ?: 'Not Specified') ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label"><i class="fas fa-calendar-alt"></i> Issue Date</div>
                        <div class="detail-value"><?= date('d F Y') ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label"><i class="fas fa-tag"></i> Certificate ID</div>
                        <div class="detail-value"><?= $certificate_id ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label"><i class="fas fa-chart-line"></i> Tests Completed</div>
                        <div class="detail-value"><?= $overall['total_tests'] ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label"><i class="fas fa-trophy"></i> Best Score</div>
                        <div class="detail-value"><?= round($overall['best_score'], 1) ?>%</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label"><i class="fas fa-star"></i> Strongest Domain</div>
                        <div class="detail-value">
                            <?php
                            $strongest = $conn->query("
                                SELECT category, AVG(percentage) as avg 
                                FROM results 
                                WHERE user_id = $user_id 
                                GROUP BY category 
                                ORDER BY avg DESC LIMIT 1
                            ")->fetch_assoc();
                            echo htmlspecialchars($strongest['category'] ?? 'All Domains');
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="overall-score">
                <div>OVERALL APTITUDE ASSESSMENT</div>
                <div class="overall-percentage"><?= round($overall_percentage, 1) ?>%</div>
                <div class="overall-grade">Overall Grade: <?= $overall_grade ?></div>
                <div class="proficiency">
                    <?php
                    if ($overall_percentage >= 80) echo "Distinction";
                    elseif ($overall_percentage >= 70) echo "Excellent";
                    elseif ($overall_percentage >= 60) echo "Very Good";
                    elseif ($overall_percentage >= 50) echo "Good";
                    elseif ($overall_percentage >= 40) echo "Satisfactory";
                    else echo "Needs Improvement";
                    ?>
                </div>
            </div>
            
            <!-- Domains Taken Section -->
            <div class="domains-taken">
                <div class="label"><i class="fas fa-layer-group"></i> Domains Assessed</div>
                <div class="domains-text">
                    <?php
                    $domain_list = [];
                    $domain_performance->data_seek(0);
                    while ($dom = $domain_performance->fetch_assoc()) {
                        $domain_list[] = $dom['category'];
                    }
                    
                    if (!empty($domain_list)) {
                        $count = count($domain_list);
                        if ($count == 1) {
                            $sentence = $domain_list[0];
                        } elseif ($count == 2) {
                            $sentence = $domain_list[0] . " and " . $domain_list[1];
                        } else {
                            $last = array_pop($domain_list);
                            $sentence = implode(", ", $domain_list) . ", and " . $last;
                        }
                        echo "The candidate was assessed in the following aptitude domains: <strong>" . $sentence . "</strong>.";
                    } else {
                        echo "No domains have been assessed yet.";
                    }
                    ?>
                </div>
            </div>
            
            <div class="career-section">
                <div class="career-title"><i class="fas fa-chart-line"></i> RECOMMENDED CAREER PATHWAYS</div>
                <div class="career-list">
                    <?php
                    // Simple career recommendations based on strongest domain
                    $strongest_domain = $conn->query("
                        SELECT category, AVG(percentage) as avg 
                        FROM results 
                        WHERE user_id = $user_id 
                        GROUP BY category 
                        ORDER BY avg DESC LIMIT 1
                    ")->fetch_assoc();
                    
                    $careers = [];
                    if ($strongest_domain) {
                        $cat = $strongest_domain['category'];
                        $score = $strongest_domain['avg'];
                        if ($cat == 'Quantitative Aptitude') {
                            if ($score >= 70) $careers = ['Data Scientist', 'Actuary', 'Financial Analyst', 'Software Engineer'];
                            elseif ($score >= 50) $careers = ['Accountant', 'Banker', 'Business Analyst', 'Engineer'];
                            else $careers = ['Retail Manager', 'Administrator', 'Sales Associate'];
                        } elseif ($cat == 'Logical Reasoning') {
                            if ($score >= 70) $careers = ['Lawyer', 'Judge', 'Detective', 'IT Security Analyst'];
                            elseif ($score >= 50) $careers = ['Police Officer', 'Paralegal', 'Programmer', 'Manager'];
                            else $careers = ['Customer Support', 'Administrative Assistant', 'Sales'];
                        } elseif ($cat == 'Verbal Ability') {
                            if ($score >= 70) $careers = ['Journalist', 'Content Writer', 'Translator', 'Public Relations Specialist'];
                            elseif ($score >= 50) $careers = ['Teacher', 'Editor', 'Marketing Specialist', 'Copywriter'];
                            else $careers = ['Receptionist', 'Call Center Agent', 'Administrative Assistant'];
                        } else {
                            $careers = ['Administrative Professional', 'Customer Service Representative', 'Sales Associate'];
                        }
                    }
                    
                    foreach($careers as $career):
                    ?>
                        <span class="career-tag"><i class="fas fa-briefcase"></i> <?= $career ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3 small text-muted">
                    <i class="fas fa-info-circle"></i> Based on performance in aptitude domains
                </div>
            </div>
            
            <div class="certificate-footer">
                <div class="signature-area">
                    <div class="signature-line"></div>
                    <div class="signature-name">Mrs. Thandi Dlamini</div>
                    <div class="signature-title">Chief Inspector - Ministry of Education</div>
                </div>
                <div class="stamp">
                    <div class="stamp-icon"><i class="fas fa-stamp"></i></div>
                    <div class="certificate-id">OFFICIAL SEAL</div>
                </div>
                <div class="qr-code">
                    <img src="<?= $qr_url ?>" alt="QR Code" class="qr-image" id="certificateQR">
                    <div class="qr-label"><i class="fas fa-qrcode"></i> Scan to Verify</div>
                    <div class="certificate-id mt-1">sats.gov.sz/verify</div>
                </div>
                <div class="signature-area">
                    <div class="signature-line"></div>
                    <div class="signature-name">Mr. Sibusiso Mamba</div>
                    <div class="signature-title">Commissioner - Ministry of Labour</div>
                </div>
            </div>
        </div>
        
        <div class="action-buttons no-print">
            <button onclick="window.print()" class="btn-print"><i class="fas fa-print"></i> Print Certificate</button>
            <a href="view_results.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Results</a>
        </div>
    </div>
    
    <script>
        const qrImage = document.getElementById('certificateQR');
        if (qrImage) {
            qrImage.addEventListener('click', function() {
                const link = document.createElement('a');
                link.download = 'certificate_qr_<?= $certificate_id ?>.png';
                link.href = this.src;
                link.click();
            });
        }
    </script>
</body>
</html>