<?php include '../config/db.php'; ?>
<?php
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

include '../includes/adaptive_engine.php';

$user_id = $_SESSION['user_id'];
$category = isset($_GET['category']) ? urldecode($_GET['category']) : '';
$topic = isset($_GET['topic']) ? urldecode($_GET['topic']) : '';
$quick = isset($_GET['quick']);

// Create test session
$test_type = $quick ? 'quick' : ($category ? 'adaptive' : 'mixed_adaptive');
$stmt = $conn->prepare("INSERT INTO tests (user_id, category, topic, test_type, start_time) VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param("isss", $user_id, $category, $topic, $test_type);
$stmt->execute();
$test_id = $conn->insert_id;

// Initialize arrays
$adaptive = null;
$questions = [];

if ($quick) {
    // Quick test - 10 random questions
    $questions_result = $conn->query("SELECT * FROM questions WHERE active = 1 ORDER BY RAND() LIMIT 10");
    while ($row = $questions_result->fetch_assoc()) {
        $questions[] = $row;
    }
} elseif ($category && $category != 'all' && $category != '') {
    // Adaptive test for specific category
    $adaptive = new AdaptiveEngine($conn, $category, 20, 0.30);
    
    // Get unique questions adaptively
    $max_attempts = 50;
    $attempt = 0;
    $question_ids = [];
    
    while (!$adaptive->shouldStop() && $attempt < $max_attempts && count($questions) < 25) {
        $next = $adaptive->selectNextItem();
        if ($next && !in_array($next['id'], $question_ids)) {
            $question_ids[] = $next['id'];
            $questions[] = $next;
        } elseif (!$next) {
            break;
        }
        $attempt++;
    }
    
    // Store adaptive state in session
    $_SESSION['adaptive_state_' . $test_id] = serialize($adaptive);
    
} else {
    // Mixed adaptive test (all categories)
    $adaptive = new AdaptiveEngine($conn, null, 20, 0.30);
    
    $max_attempts = 50;
    $attempt = 0;
    $question_ids = [];
    
    while (!$adaptive->shouldStop() && $attempt < $max_attempts && count($questions) < 25) {
        $next = $adaptive->selectNextItem();
        if ($next && !in_array($next['id'], $question_ids)) {
            $question_ids[] = $next['id'];
            $questions[] = $next;
        } elseif (!$next) {
            break;
        }
        $attempt++;
    }
    
    $_SESSION['adaptive_state_' . $test_id] = serialize($adaptive);
}

// If no questions were loaded, show error
if (empty($questions)) {
    die("<div class='alert alert-danger'>No questions available. Please contact administrator.</div>");
}

// Shuffle questions for better variety (optional)
shuffle($questions);

// Calculate timer based on number of questions
$total_questions_count = count($questions);
$time_limit = max(300, $total_questions_count * 45); // 45 seconds per question, minimum 5 minutes (300 seconds)
$time_minutes = floor($time_limit / 60);
$time_seconds = $time_limit % 60;
$time_display = sprintf("%02d:%02d", $time_minutes, $time_seconds);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Adaptive Test - Smart Aptitude Testing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        body {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding: 20px;
        }
        .test-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .timer-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
            z-index: 100;
        }
        .timer {
            font-size: 3rem;
            font-weight: bold;
            font-family: monospace;
            color: #dc3545;
        }
        .question-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            display: none;
            animation: slideInRight 0.5s ease;
        }
        .question-card.active {
            display: block;
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .option-btn {
            width: 100%;
            text-align: left;
            padding: 15px 20px;
            margin: 10px 0;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            background: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }
        .option-btn:hover {
            transform: translateX(8px);
            border-color: var(--secondary);
            background: #f8f9ff;
        }
        .option-btn.selected {
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            color: white;
            border-color: var(--primary);
        }
        .progress-dots {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
            margin: 20px 0;
        }
        .progress-dot {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 2px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        .progress-dot:hover {
            transform: scale(1.2);
        }
        .progress-dot.answered {
            background: var(--success);
            border-color: var(--success);
            color: white;
        }
        .progress-dot.current {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            transform: scale(1.1);
            animation: pulse 0.5s ease;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1.1); }
            50% { transform: scale(1.2); }
        }
        .nav-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .question-number {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        .category-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .badge-quant { background: #e3f2fd; color: #1565c0; }
        .badge-logic { background: #fff3e0; color: #e65100; }
        .badge-verbal { background: #e8f5e9; color: #2e7d32; }
        
        @media (max-width: 768px) {
            .test-container { margin: 0; }
            .question-card { padding: 20px; }
            .option-btn { padding: 12px 15px; }
            .progress-dot { width: 35px; height: 35px; font-size: 12px; }
            .timer { font-size: 2rem; }
        }
        
        .offline-toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #ffc107;
            color: #333;
            padding: 12px 20px;
            border-radius: 10px;
            z-index: 1500;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
    <script>
        // Apply theme based on category
        const urlParams = new URLSearchParams(window.location.search);
        const category = urlParams.get('category');
        
        let themeMap = {
            'Quantitative Aptitude': 'quantitative',
            'Logical Reasoning': 'logical',
            'Verbal Ability': 'verbal'
        };
        
        let testTheme = themeMap[category] || 'mixed';
        document.body.setAttribute('data-theme', testTheme);
    </script>
</head>
<body>
    <div class="test-container">
        <div class="timer-card">
            <i class="fas fa-hourglass-half"></i> Time Remaining
            <!-- Updated timer display with dynamic time based on question count -->
            <div class="timer" id="timer"><?= $time_display ?></div>
            <div class="small text-muted mt-1">
                <i class="fas fa-clock"></i> <?= $total_questions_count ?> questions • Estimated time: <?= $time_minutes ?> minutes
                <?php if($adaptive): ?>
                <span class="badge bg-info ms-2"><i class="fas fa-brain"></i> Adaptive</span>
                <?php endif; ?>
                <span class="badge bg-secondary ms-2"><i class="fas fa-question-circle"></i> <?= count($questions) ?> Questions</span>
            </div>
        </div>
        
        <form method="POST" action="result.php" id="testForm">
            <input type="hidden" name="test_id" value="<?= $test_id ?>">
            <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
            <input type="hidden" name="topic" value="<?= htmlspecialchars($topic) ?>">
            <input type="hidden" name="quick" value="<?= $quick ?>">
            <input type="hidden" name="adaptive" value="<?= $adaptive ? 1 : 0 ?>">
            <input type="hidden" name="total_questions" value="<?= count($questions) ?>">
            
            <?php $index = 0; foreach($questions as $q): 
                $category_class = '';
                if (strpos($q['category'], 'Quantitative') !== false) $category_class = 'badge-quant';
                elseif (strpos($q['category'], 'Logical') !== false) $category_class = 'badge-logic';
                else $category_class = 'badge-verbal';
            ?>
            <div class="question-card" id="q_<?= $q['id'] ?>" data-index="<?= $index ?>">
                <div class="question-number">
                    <span class="category-badge <?= $category_class ?>">
                        <i class="fas fa-folder"></i> <?= htmlspecialchars($q['category']) ?>
                    </span>
                    <span class="float-end">Question <span class="q-num"><?= $index + 1 ?></span> of <?= count($questions) ?></span>
                </div>
                <h4 class="mb-4"><?= nl2br(htmlspecialchars($q['question'])) ?></h4>
                <div class="mt-4">
                    <?php foreach(['a', 'b', 'c', 'd'] as $opt): 
                        $opt_value = $q['option_' . $opt];
                        if (!empty($opt_value)):
                    ?>
                    <div class="option-btn" data-qid="<?= $q['id'] ?>" data-value="<?= htmlspecialchars($opt_value) ?>">
                        <strong><?= strtoupper($opt) ?>.</strong> <?= htmlspecialchars($opt_value) ?>
                    </div>
                    <?php endif; endforeach; ?>
                </div>
                <input type="hidden" name="answer_<?= $q['id'] ?>" id="answer_<?= $q['id'] ?>" value="">
                <input type="hidden" name="correct_<?= $q['id'] ?>" value="<?= htmlspecialchars($q['correct_answer']) ?>">
                <input type="hidden" name="difficulty_<?= $q['id'] ?>" value="<?= $q['difficulty'] ?>">
                <div class="small text-muted mt-3">
                    <i class="fas fa-chart-line"></i> Difficulty: <?= number_format($q['difficulty'], 2) ?>
                </div>
            </div>
            <?php $index++; endforeach; ?>
            
            <div class="progress-dots" id="progressDots"></div>
            
            <div class="nav-buttons">
                <button type="button" class="btn btn-secondary btn-lg" id="prevBtn" disabled>
                    <i class="fas fa-chevron-left"></i> Previous
                </button>
                <button type="button" class="btn btn-primary btn-lg" id="nextBtn">
                    Next <i class="fas fa-chevron-right"></i>
                </button>
                <button type="submit" class="btn btn-success btn-lg" id="submitBtn" style="display: none;">
                    <i class="fas fa-check"></i> Submit Test
                </button>
            </div>
        </form>
    </div>
    
    <script src="../assets/js/offline.js"></script>
    <script>
        const questions = <?= json_encode($questions) ?>;
        const testId = <?= $test_id ?>;
        const userId = <?= $user_id ?>;
        let currentIndex = 0;
        let answers = {};
        // Updated timer seconds based on dynamic calculation
        let timerSeconds = <?= $time_limit ?>;
        let timerInterval;
        let answerTimes = {};
        let questionStartTimes = {};
        
        // Load saved answers from localStorage if any
        const savedAnswers = localStorage.getItem('test_answers_' + testId);
        if (savedAnswers) {
            answers = JSON.parse(savedAnswers);
        }
        
        function updateTimer() {
            const minutes = Math.floor(timerSeconds / 60);
            const seconds = timerSeconds % 60;
            document.getElementById('timer').textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            // Warning when 1 minute remaining
            if (timerSeconds === 60) {
                showOfflineNotification('⚠️ 1 minute remaining! Hurry up!');
            }
            
            if (timerSeconds <= 0) {
                clearInterval(timerInterval);
                alert('Time is up! Submitting your test...');
                document.getElementById('testForm').submit();
            }
            timerSeconds--;
        }
        
        timerInterval = setInterval(updateTimer, 1000);
        
        function showQuestion(index) {
            // Hide all questions
            document.querySelectorAll('.question-card').forEach(card => {
                card.classList.remove('active');
            });
            
            // Show current question
            const currentCard = document.getElementById(`q_${questions[index].id}`);
            if (currentCard) {
                currentCard.classList.add('active');
                questionStartTimes[questions[index].id] = Date.now();
            }
            
            // Update navigation buttons
            document.getElementById('prevBtn').disabled = (index === 0);
            document.getElementById('nextBtn').style.display = (index === questions.length - 1) ? 'none' : 'inline-block';
            document.getElementById('submitBtn').style.display = (index === questions.length - 1) ? 'inline-block' : 'none';
            
            // Update progress dots
            updateProgressDots();
        }
        
        function updateProgressDots() {
            const container = document.getElementById('progressDots');
            container.innerHTML = '';
            
            questions.forEach((q, idx) => {
                const dot = document.createElement('div');
                dot.className = 'progress-dot';
                dot.textContent = idx + 1;
                if (answers[q.id]) {
                    dot.classList.add('answered');
                }
                if (idx === currentIndex) {
                    dot.classList.add('current');
                }
                dot.onclick = () => {
                    currentIndex = idx;
                    showQuestion(currentIndex);
                };
                container.appendChild(dot);
            });
        }
        
        async function saveAnswerOffline(qid, answer, timeTaken) {
            const offlineData = {
                test_id: testId,
                question_id: qid,
                answer: answer,
                time_taken: timeTaken,
                user_id: userId,
                timestamp: new Date().toISOString()
            };
            
            if (navigator.onLine) {
                try {
                    await fetch('/aptitude-system/api/save_result.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'response', ...offlineData })
                    });
                } catch (err) {
                    console.log('Save failed:', err);
                    if (typeof saveOfflineResponse === 'function') {
                        await saveOfflineResponse(offlineData);
                        showOfflineNotification('Saved offline. Will sync when online.');
                    }
                }
            } else {
                if (typeof saveOfflineResponse === 'function') {
                    await saveOfflineResponse(offlineData);
                    showOfflineNotification('📡 Offline mode: Answer saved locally');
                }
            }
        }
        
        function showOfflineNotification(message) {
            const toast = document.createElement('div');
            toast.className = 'offline-toast';
            toast.innerHTML = `<i class="fas fa-wifi"></i> ${message}`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
        
        // Add click handlers to option buttons
        document.querySelectorAll('.option-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const qid = this.dataset.qid;
                const value = this.dataset.value;
                const timeTaken = Math.round((Date.now() - (questionStartTimes[qid] || Date.now())) / 1000);
                
                // Store answer
                answers[qid] = value;
                document.getElementById(`answer_${qid}`).value = value;
                answerTimes[qid] = timeTaken;
                
                // Update styling
                const parentCard = this.closest('.question-card');
                parentCard.querySelectorAll('.option-btn').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                
                // Save offline
                await saveAnswerOffline(qid, value, timeTaken);
                
                // Save to localStorage
                localStorage.setItem('test_answers_' + testId, JSON.stringify(answers));
                
                updateProgressDots();
                
                // Auto advance to next question
                if (currentIndex < questions.length - 1) {
                    setTimeout(() => {
                        currentIndex++;
                        showQuestion(currentIndex);
                    }, 300);
                }
            });
            
            // Pre-select if answer exists
            const qid = btn.dataset.qid;
            if (answers[qid] && btn.dataset.value === answers[qid]) {
                btn.classList.add('selected');
                document.getElementById(`answer_${qid}`).value = answers[qid];
            }
        });
        
        // Navigation buttons
        document.getElementById('prevBtn').addEventListener('click', () => {
            if (currentIndex > 0) {
                currentIndex--;
                showQuestion(currentIndex);
            }
        });
        
        document.getElementById('nextBtn').addEventListener('click', () => {
            if (currentIndex < questions.length - 1) {
                currentIndex++;
                showQuestion(currentIndex);
            }
        });
        
        // Save before submit
        document.getElementById('testForm').addEventListener('submit', function() {
            localStorage.removeItem('test_answers_' + testId);
        });
        
        // Online/Offline listeners
        window.addEventListener('online', () => {
            showOfflineNotification('✅ Back online! Syncing your answers...');
            if (typeof syncOfflineData === 'function') {
                syncOfflineData();
            }
        });
        
        window.addEventListener('offline', () => {
            showOfflineNotification('📡 You are offline. Answers will be saved locally.');
        });
        
        // Show first question
        if (questions.length > 0) {
            showQuestion(0);
        } else {
            document.querySelector('.test-container').innerHTML = '<div class="alert alert-danger">No questions available. Please contact administrator.</div>';
        }
    </script>
</body>
</html>