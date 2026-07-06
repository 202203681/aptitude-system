<?php include '../config/db.php'; ?>
<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Handle new category addition
$new_category_added = false;
$category_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_category') {
        $new_category = trim($_POST['new_category']);
        if (!empty($new_category)) {
            // Check if category already exists in questions table
            $check = $conn->prepare("SELECT category FROM questions WHERE category = ? LIMIT 1");
            $check->bind_param("s", $new_category);
            $check->execute();
            $check->store_result();
            
            if ($check->num_rows == 0) {
                // Category doesn't exist - we can add a placeholder question later or just note it's available
                $new_category_added = true;
                $success_msg = "Category '$new_category' is ready. You can now add questions to this category.";
            } else {
                $category_error = "Category '$new_category' already exists!";
            }
        }
    }
}

// Handle question addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_question'])) {
    $stmt = $conn->prepare("INSERT INTO questions (category, topic, sub_domain, question, option_a, option_b, option_c, option_d, correct_answer, difficulty, discrimination, explanation, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $active = isset($_POST['active']) ? 1 : 0;
    $stmt->bind_param("sssssssssddds", $_POST['category'], $_POST['topic'], $_POST['sub_domain'], $_POST['question'], $_POST['option_a'], $_POST['option_b'], $_POST['option_c'], $_POST['option_d'], $_POST['correct_answer'], $_POST['difficulty'], $_POST['discrimination'], $_POST['explanation'], $active);
    
    if ($stmt->execute()) {
        $question_success = true;
        // Log the action
        logSystemAction($conn, $_SESSION['user_id'], 'add_question', ['category' => $_POST['category'], 'topic' => $_POST['topic']]);
    } else {
        $question_error = $conn->error;
    }
}

// Get all existing categories from questions table (FIXED - no categories table)
$categories = $conn->query("SELECT DISTINCT category FROM questions WHERE active = 1 ORDER BY category");
$category_list = [];
if ($categories) {
    while ($cat = $categories->fetch_assoc()) {
        $category_list[] = $cat['category'];
    }
}

// Get all topics for autocomplete
$topics = $conn->query("SELECT DISTINCT topic FROM questions WHERE topic IS NOT NULL AND topic != '' ORDER BY topic");
$topic_list = [];
while ($topic = $topics->fetch_assoc()) {
    $topic_list[] = $topic['topic'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Question - SATS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        body { background: #f0f2f5; }
        .form-container {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .category-manager {
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            color: white;
        }
        .category-tag {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 20px;
            margin: 3px;
            font-size: 0.85rem;
        }
        .category-tag:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.05);
        }
        .delete-category {
            cursor: pointer;
            margin-left: 8px;
            color: #ff9999;
        }
        .delete-category:hover {
            color: #ff0000;
        }
        .btn-category {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
        }
        .btn-category:hover {
            background: rgba(255,255,255,0.3);
            color: white;
        }
        .existing-categories {
            max-height: 150px;
            overflow-y: auto;
            margin-top: 15px;
        }
        .form-label {
            font-weight: 600;
            color: var(--primary);
        }
        .question-preview {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            display: none;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert-animated {
            animation: fadeIn 0.5s ease;
        }
    </style>
</head>
<body data-theme="mixed">
    <div class="container mt-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-plus-circle"></i> Add New Question</h2>
            <div>
                <a href="manage_questions.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Questions
                </a>
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (isset($question_success)): ?>
            <div class="alert alert-success alert-dismissible fade show alert-animated" role="alert">
                <i class="fas fa-check-circle"></i> Question added successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($question_error)): ?>
            <div class="alert alert-danger alert-dismissible fade show alert-animated" role="alert">
                <i class="fas fa-exclamation-circle"></i> Error: <?= $question_error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($new_category_added): ?>
            <div class="alert alert-success alert-dismissible fade show alert-animated" role="alert">
                <i class="fas fa-check-circle"></i> <?= $success_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($category_error): ?>
            <div class="alert alert-danger alert-dismissible fade show alert-animated" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?= $category_error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Category Manager Section -->
        <div class="category-manager">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5><i class="fas fa-tags"></i> Category Manager</h5>
                    <p class="mb-0 small">Manage your question categories. Add new categories or use existing ones.</p>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-category" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="fas fa-plus"></i> Add New Category
                    </button>
                </div>
            </div>
            
            <!-- Existing Categories Display -->
            <div class="existing-categories">
                <strong>Existing Categories:</strong>
                <?php if (empty($category_list)): ?>
                    <span class="text-muted">No categories yet. Add your first category above.</span>
                <?php else: ?>
                    <?php foreach ($category_list as $cat): ?>
                        <span class="category-tag">
                            <?= htmlspecialchars($cat) ?>
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Add Question Form -->
        <div class="form-container">
            <h4><i class="fas fa-question-circle"></i> Question Details</h4>
            <form method="POST" id="questionForm">
                <input type="hidden" name="add_question" value="1">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Category *</label>
                        <div class="input-group">
                            <select name="category" id="category" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($category_list as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                                <option value="__new__">+ Add New Category...</option>
                            </select>
                        </div>
                        <small class="text-muted">Select an existing category or choose "Add New Category"</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Topic *</label>
                        <div class="input-group">
                            <input type="text" name="topic" id="topic" class="form-control" list="topicList" required placeholder="e.g., Percentages, Algebra, Vocabulary">
                            <datalist id="topicList">
                                <?php foreach ($topic_list as $topic): ?>
                                    <option value="<?= htmlspecialchars($topic) ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <button type="button" class="btn btn-outline-secondary" onclick="addNewTopic()" title="Add new topic">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <small class="text-muted">Type or select existing topic, or click + to add new</small>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Sub-domain (Optional)</label>
                        <input type="text" name="sub_domain" class="form-control" placeholder="e.g., Percentage Calculations, Linear Equations">
                    </div>
                    
                    <div class="col-12 mb-3">
                        <label class="form-label">Question *</label>
                        <textarea name="question" class="form-control" rows="3" required placeholder="Enter the question text here..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Option A *</label>
                            <input type="text" name="option_a" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Option B *</label>
                            <input type="text" name="option_b" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Option C *</label>
                            <input type="text" name="option_c" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Option D *</label>
                            <input type="text" name="option_d" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Correct Answer *</label>
                            <select name="correct_answer" class="form-select" required>
                                <option value="">-- Select --</option>
                                <option value="option_a">Option A</option>
                                <option value="option_b">Option B</option>
                                <option value="option_c">Option C</option>
                                <option value="option_d">Option D</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Difficulty (IRT b-parameter)</label>
                            <input type="number" step="0.1" name="difficulty" class="form-control" value="0" placeholder="-3 to +3">
                            <small class="text-muted">Negative = Easy, Positive = Hard</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Discrimination (a-parameter)</label>
                            <input type="number" step="0.1" name="discrimination" class="form-control" value="1.0" placeholder="0.5 to 2.5">
                            <small class="text-muted">Higher = Better discrimination</small>
                        </div>
                    </div>
                    
                    <div class="col-12 mb-3">
                        <label class="form-label">Explanation (Optional)</label>
                        <textarea name="explanation" class="form-control" rows="2" placeholder="Explain why this answer is correct..."></textarea>
                    </div>
                    
                    <div class="col-12 mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="active" class="form-check-input" id="active" value="1" checked>
                            <label class="form-check-label" for="active">Active (question will appear in tests)</label>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <button type="button" class="btn btn-info" onclick="previewQuestion()">
                            <i class="fas fa-eye"></i> Preview Question
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Question
                        </button>
                        <button type="reset" class="btn btn-secondary" onclick="resetForm()">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
            </form>
            
            <!-- Question Preview -->
            <div id="questionPreview" class="question-preview">
                <h6><i class="fas fa-eye"></i> Question Preview</h6>
                <div id="previewContent"></div>
            </div>
        </div>
        
        <!-- Category Management Tips -->
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            <strong>Category Management Tips:</strong>
            <ul class="mb-0 mt-2">
                <li>Categories appear automatically in student dashboards once questions are added</li>
                <li>You can add unlimited categories to expand the testing system</li>
                <li>Popular categories include: Life Skills, Computer Literacy, Science, Business Studies, etc.</li>
            </ul>
        </div>
    </div>
    
    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white;">
                    <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Add New Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_category">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Category Name *</label>
                            <input type="text" name="new_category" class="form-control" required placeholder="e.g., Business Studies, Geography, History">
                            <small class="text-muted">This will create a new category that students can select for testing.</small>
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-lightbulb"></i> 
                            <strong>Suggested Categories:</strong>
                            <div class="mt-2">
                                <span class="badge bg-secondary me-1" onclick="fillCategory('Business Studies')">Business Studies</span>
                                <span class="badge bg-secondary me-1" onclick="fillCategory('Geography')">Geography</span>
                                <span class="badge bg-secondary me-1" onclick="fillCategory('History')">History</span>
                                <span class="badge bg-secondary me-1" onclick="fillCategory('Economics')">Economics</span>
                                <span class="badge bg-secondary me-1" onclick="fillCategory('Physics')">Physics</span>
                                <span class="badge bg-secondary me-1" onclick="fillCategory('Chemistry')">Chemistry</span>
                                <span class="badge bg-secondary me-1" onclick="fillCategory('Biology')">Biology</span>
                                <span class="badge bg-secondary me-1" onclick="fillCategory('Agriculture')">Agriculture</span>
                                <span class="badge bg-secondary me-1" onclick="fillCategory('Home Economics')">Home Economics</span>
                                <span class="badge bg-secondary me-1" onclick="fillCategory('Design & Technology')">Design & Technology</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function fillCategory(categoryName) {
            document.querySelector('input[name="new_category"]').value = categoryName;
        }
        
        function previewQuestion() {
            const category = document.getElementById('category').value;
            const topic = document.getElementById('topic').value;
            const question = document.querySelector('textarea[name="question"]').value;
            const option_a = document.querySelector('input[name="option_a"]').value;
            const option_b = document.querySelector('input[name="option_b"]').value;
            const option_c = document.querySelector('input[name="option_c"]').value;
            const option_d = document.querySelector('input[name="option_d"]').value;
            const correct = document.querySelector('select[name="correct_answer"]').value;
            
            if (!question) {
                alert('Please enter a question first');
                return;
            }
            
            const previewDiv = document.getElementById('questionPreview');
            const contentDiv = document.getElementById('previewContent');
            
            let correctLetter = '';
            if (correct === 'option_a') correctLetter = 'A';
            else if (correct === 'option_b') correctLetter = 'B';
            else if (correct === 'option_c') correctLetter = 'C';
            else if (correct === 'option_d') correctLetter = 'D';
            
            contentDiv.innerHTML = `
                <div class="card">
                    <div class="card-body">
                        <span class="badge bg-primary mb-2">${category || 'No Category'}</span>
                        <span class="badge bg-secondary mb-2">${topic || 'No Topic'}</span>
                        <h6>${question}</h6>
                        <div class="mt-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" disabled>
                                <label class="form-check-label">A. ${option_a || 'Not set'}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" disabled>
                                <label class="form-check-label">B. ${option_b || 'Not set'}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" disabled>
                                <label class="form-check-label">C. ${option_c || 'Not set'}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" disabled>
                                <label class="form-check-label">D. ${option_d || 'Not set'}</label>
                            </div>
                        </div>
                        ${correctLetter ? `<div class="alert alert-success mt-3"><strong>Correct Answer:</strong> Option ${correctLetter}</div>` : ''}
                    </div>
                </div>
            `;
            
            previewDiv.style.display = 'block';
            previewDiv.scrollIntoView({ behavior: 'smooth' });
        }
        
        function resetForm() {
            document.getElementById('questionForm').reset();
            document.getElementById('questionPreview').style.display = 'none';
        }
        
        function addNewTopic() {
            const newTopic = prompt('Enter new topic name:');
            if (newTopic && newTopic.trim()) {
                document.getElementById('topic').value = newTopic.trim();
                // Add to datalist
                const datalist = document.getElementById('topicList');
                const option = document.createElement('option');
                option.value = newTopic.trim();
                datalist.appendChild(option);
            }
        }
        
        // Handle category selection
        document.getElementById('category').addEventListener('change', function() {
            if (this.value === '__new__') {
                // Open the add category modal
                const modal = new bootstrap.Modal(document.getElementById('addCategoryModal'));
                modal.show();
                // Reset selection
                setTimeout(() => {
                    document.getElementById('category').value = '';
                }, 100);
            }
        });
        
        // Auto-save draft (optional)
        let autoSaveTimer;
        const formInputs = document.querySelectorAll('#questionForm input, #questionForm textarea, #questionForm select');
        formInputs.forEach(input => {
            input.addEventListener('input', function() {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(() => {
                    localStorage.setItem('question_draft', JSON.stringify({
                        category: document.getElementById('category').value,
                        topic: document.getElementById('topic').value,
                        sub_domain: document.querySelector('input[name="sub_domain"]').value,
                        question: document.querySelector('textarea[name="question"]').value,
                        option_a: document.querySelector('input[name="option_a"]').value,
                        option_b: document.querySelector('input[name="option_b"]').value,
                        option_c: document.querySelector('input[name="option_c"]').value,
                        option_d: document.querySelector('input[name="option_d"]').value,
                        difficulty: document.querySelector('input[name="difficulty"]').value,
                        discrimination: document.querySelector('input[name="discrimination"]').value,
                        explanation: document.querySelector('textarea[name="explanation"]').value
                    }));
                    console.log('Draft saved');
                }, 2000);
            });
        });
        
        // Load draft on page load
        const draft = localStorage.getItem('question_draft');
        if (draft) {
            const data = JSON.parse(draft);
            if (confirm('You have an unsaved draft. Load it?')) {
                document.getElementById('category').value = data.category;
                document.getElementById('topic').value = data.topic;
                document.querySelector('input[name="sub_domain"]').value = data.sub_domain;
                document.querySelector('textarea[name="question"]').value = data.question;
                document.querySelector('input[name="option_a"]').value = data.option_a;
                document.querySelector('input[name="option_b"]').value = data.option_b;
                document.querySelector('input[name="option_c"]').value = data.option_c;
                document.querySelector('input[name="option_d"]').value = data.option_d;
                document.querySelector('input[name="difficulty"]').value = data.difficulty;
                document.querySelector('input[name="discrimination"]').value = data.discrimination;
                document.querySelector('textarea[name="explanation"]').value = data.explanation;
            }
            // Clear draft after loading
            localStorage.removeItem('question_draft');
        }
    </script>
</body>
</html>