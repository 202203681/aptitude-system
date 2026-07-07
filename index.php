<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Smart Aptitude Testing System - Ministry of Education Eswatini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="manifest" href="manifest.json">
    <style>
        :root {
            --primary: #003399;
            --primary-dark: #001a66;
            --secondary: #CE9F32;
            --secondary-dark: #b8860b;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        /* Navbar Styles */
        .navbar-custom {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white !important;
        }
        
        .navbar-brand i {
            color: var(--secondary);
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }
        
        .btn-nav-register {
            background: var(--secondary);
            color: var(--primary) !important;
            border-radius: 25px;
            padding: 8px 20px !important;
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            min-height: 90vh;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.05)" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
            opacity: 0.3;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            padding: 80px 0;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 20px;
            animation: fadeInUp 0.8s ease;
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
            animation: fadeInUp 0.8s ease 0.2s forwards;
            opacity: 0;
            animation-fill-mode: forwards;
        }
        
        .hero-buttons {
            animation: fadeInUp 0.8s ease 0.4s forwards;
            opacity: 0;
            animation-fill-mode: forwards;
        }
        
        .btn-custom {
            padding: 12px 35px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            margin: 5px;
        }
        
        .btn-primary-custom {
            background: var(--secondary);
            color: var(--primary-dark);
            border: none;
        }
        
        .btn-primary-custom:hover {
            background: var(--secondary-dark);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .btn-outline-custom {
            background: transparent;
            border: 2px solid white;
            color: white;
        }
        
        .btn-outline-custom:hover {
            background: white;
            color: var(--primary);
            transform: translateY(-3px);
        }
        
        /* Features Section */
        .features {
            padding: 80px 0;
            background: #f8f9fa;
        }
        
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 50px;
            text-align: center;
        }
        
        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            height: 100%;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 2rem;
        }
        
        /* Stats Section */
        .stats {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 60px 0;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 10px;
        }
        
        /* Footer */
        .footer {
            background: #1a2a3a;
            color: #aaa;
            padding: 50px 0 20px;
        }
        
        .footer a {
            color: #aaa;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer a:hover {
            color: var(--secondary);
        }
        
        /* Animations */
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
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .float-animation {
            animation: float 4s ease-in-out infinite;
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .section-title {
                font-size: 1.8rem;
            }
            
            .stat-number {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-brain"></i> SATS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">How It Works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="auth/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn-nav-register" href="auth/register.php">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container hero-content">
            <div class="row align-items-center">
                <div class="col-lg-7 text-white">
                    <h1 class="hero-title">
                        <i class="fas fa-brain"></i><br>
                        Smart Aptitude Testing System
                    </h1>
                    <p class="hero-subtitle">
                        Empowering Eswatini students to discover their potential through intelligent, adaptive aptitude testing. 
                        Get personalized career recommendations and track your progress.
                    </p>
                    <div class="hero-buttons">
                        <a href="auth/register.php" class="btn btn-primary-custom btn-custom">
                            <i class="fas fa-user-plus"></i> Get Started
                        </a>
                        <a href="auth/login.php" class="btn btn-outline-custom btn-custom">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </div>
                </div>
                <div class="col-lg-5 text-center d-none d-lg-block">
                    <i class="fas fa-chart-line fa-8x text-white float-animation" style="opacity: 0.8;"></i>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <h2 class="section-title">Why Choose Our System?</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-robot"></i></div>
                        <h3>Adaptive Testing</h3>
                        <p class="text-muted">Questions adjust to your skill level using Item Response Theory (IRT).</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-briefcase"></i></div>
                        <h3>Career Recommendations</h3>
                        <p class="text-muted">Get personalized career path suggestions based on your strengths.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-certificate"></i></div>
                        <h3>Digital Certificates</h3>
                        <p class="text-muted">Earn verifiable certificates with QR codes for easy verification.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
                        <h3>Mobile-Friendly PWA</h3>
                        <p class="text-muted">Take tests anywhere, even offline. Install as an app on your phone.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-chart-pie"></i></div>
                        <h3>Detailed Analytics</h3>
                        <p class="text-muted">Track your progress with detailed reports and insights.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                        <h3>Secure & Reliable</h3>
                        <p class="text-muted">Bank-grade security with anti-cheating measures.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 mb-4">
                    <div class="stat-number" id="statQuestions">0</div>
                    <div class="stat-label">Practice Questions</div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stat-number" id="statStudents">0</div>
                    <div class="stat-label">Active Students</div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stat-number" id="statTests">0</div>
                    <div class="stat-label">Tests Completed</div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="stat-number" id="statCertificates">0</div>
                    <div class="stat-label">Certificates Issued</div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- How It Works -->
    <section class="features" id="how-it-works" style="background: white;">
        <div class="container">
            <h2 class="section-title">How It Works</h2>
            <div class="row">
                <div class="col-md-3">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: var(--secondary);">1</div>
                        <h3>Register</h3>
                        <p>Create your free account with your student ID</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: var(--secondary);">2</div>
                        <h3>Take Tests</h3>
                        <p>Choose a category and start adaptive testing</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: var(--secondary);">3</div>
                        <h3>Get Results</h3>
                        <p>Receive instant scores and performance analysis</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-card">
                        <div class="feature-icon" style="background: var(--secondary);">4</div>
                        <h3>Earn Certificate</h3>
                        <p>Download your verifiable digital certificate</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="stats" style="background: linear-gradient(135deg, var(--secondary), var(--secondary-dark));">
        <div class="container text-center">
            <h2 class="mb-3" style="color: var(--primary-dark);">Ready to Start Your Journey?</h2>
            <p class="mb-4" style="color: var(--primary-dark);">Join thousands of Eswatini students discovering their career paths</p>
            <a href="auth/register.php" class="btn btn-custom" style="background: var(--primary); color: white;">
                <i class="fas fa-user-plus"></i> Register Now
            </a>
            <a href="auth/login.php" class="btn btn-custom" style="background: transparent; border: 2px solid var(--primary); color: var(--primary-dark);">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5><i class="fas fa-brain"></i> SATS</h5>
                    <p>Smart Aptitude Testing System<br>Ministry of Education & Training<br>Kingdom of Eswatini</p>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#how-it-works">How It Works</a></li>
                        <li><a href="auth/register.php">Register</a></li>
                        <li><a href="auth/login.php">Login</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Contact</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope"></i> info@education.gov.sz</li>
                        <li><i class="fas fa-phone"></i> +268 2404 0000</li>
                        <li><i class="fas fa-map-marker-alt"></i> Mbabane, Eswatini</li>
                    </ul>
                </div>
            </div>
            <hr class="mt-4">
            <div class="text-center">
                <p>&copy; 2026 Ministry of Education & Training, Kingdom of Eswatini. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function animateStats() {
            const questionsEl = document.getElementById('statQuestions');
            const studentsEl = document.getElementById('statStudents');
            const testsEl = document.getElementById('statTests');
            const certificatesEl = document.getElementById('statCertificates');
            
            fetch('api/get_stats.php')
                .then(response => response.json())
                .then(data => {
                    animateNumber(questionsEl, 0, data.questions || 50, 2000);
                    animateNumber(studentsEl, 0, data.students || 0, 2000);
                    animateNumber(testsEl, 0, data.tests || 0, 2000);
                    animateNumber(certificatesEl, 0, data.certificates || 0, 2000);
                })
                .catch(err => {
                    animateNumber(questionsEl, 0, 50, 2000);
                    animateNumber(studentsEl, 0, 100, 2000);
                    animateNumber(testsEl, 0, 250, 2000);
                    animateNumber(certificatesEl, 0, 75, 2000);
                });
        }
        
        function animateNumber(element, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                const current = Math.floor(progress * (end - start) + start);
                element.textContent = current.toLocaleString();
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateStats();
                    observer.disconnect();
                }
            });
        });
        
        const statsSection = document.querySelector('.stats');
        if (statsSection) observer.observe(statsSection);
    </script>
</body>
</html>