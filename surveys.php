<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database configuration
require_once 'backend/config/database.php';

// Get user data
$user_name = $_SESSION['user_name'] ?? 'User';
$user_email = $_SESSION['user_email'] ?? '';
$user_role = $_SESSION['user_role'] ?? 'User';
$user_avatar = $_SESSION['user_avatar'] ?? 'U';

// Get surveys data
$surveys_data = [];
try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get all surveys with response counts
    $query = "SELECT s.*, 
                COUNT(DISTINCT sr.id) as response_count,
                COUNT(DISTINCT sq.id) as question_count,
                c.name as campaign_name
              FROM surveys s 
              LEFT JOIN survey_responses sr ON s.id = sr.survey_id 
              LEFT JOIN survey_questions sq ON s.id = sq.survey_id 
              LEFT JOIN campaigns c ON s.campaign_id = c.id
              GROUP BY s.id 
              ORDER BY s.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $surveys_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Surveys data error: " . $e->getMessage());
    $surveys_data = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey Management - Public Safety Campaign System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 20px;
        }
        
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
            transition: background 0.3s;
        }
        
        .sidebar a:hover, .sidebar a.active {
            background: #34495e;
            color: white;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .navbar {
            background: #34495e !important;
            margin-left: 250px;
        }
        
        .card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-badge.active { background: #d4edda; color: #155724; }
        .status-badge.draft { background: #fff3cd; color: #856404; }
        .status-badge.closed { background: #cce5ff; color: #004085; }
        .status-badge.archived { background: #f8d7da; color: #721c24; }
        
        .response-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .response-fill {
            height: 100%;
            background: linear-gradient(90deg, #007bff, #0056b3);
            transition: width 0.3s;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .nav-tabs .nav-link {
            color: #6c757d;
            border: none;
            border-bottom: 3px solid transparent;
        }
        
        .nav-tabs .nav-link.active {
            color: #495057;
            border-bottom-color: #007bff;
            background: none;
        }

        .question-item {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 10px;
            background: #f8f9fa;
        }

        .question-type-badge {
            background: #e9ecef;
            color: #495057;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="px-3 mb-4">
            <h4><i class="fas fa-shield-alt"></i> Safety Campaign</h4>
        </div>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="campaigns.php"><i class="fas fa-bullhorn"></i> Campaigns</a>
        <a href="audience.php"><i class="fas fa-users"></i> Audience</a>
        <a href="sms-alerts.php"><i class="fas fa-sms"></i> SMS Alerts</a>
        <a href="surveys.php" class="active"><i class="fas fa-poll"></i> Surveys</a>
        <a href="users.php"><i class="fas fa-user-cog"></i> Users</a>
        <div class="border-top mt-3 pt-3">
            <a href="?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">Survey Management</span>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2>Survey Management</h2>
                            <p class="text-muted">Create and manage feedback surveys for your campaigns</p>
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newSurveyModal">
                            <i class="fas fa-plus"></i> New Survey
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-poll fa-2x text-primary mb-2"></i>
                            <h3><?php echo count($surveys_data); ?></h3>
                            <p class="text-muted mb-0">Total Surveys</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-play fa-2x text-success mb-2"></i>
                            <h3><?php echo count(array_filter($surveys_data, function($s) { return $s['status'] === 'active'; })); ?></h3>
                            <p class="text-muted mb-0">Active Surveys</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-comments fa-2x text-warning mb-2"></i>
                            <h3><?php echo array_sum(array_column($surveys_data, 'response_count')); ?></h3>
                            <p class="text-muted mb-0">Total Responses</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-line fa-2x text-info mb-2"></i>
                            <h3>68%</h3>
                            <p class="text-muted mb-0">Avg Response Rate</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Surveys Table -->
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" href="#all-surveys" data-bs-toggle="tab">All Surveys</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#active-surveys" data-bs-toggle="tab">Active</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#survey-analytics" data-bs-toggle="tab">Analytics</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#survey-builder" data-bs-toggle="tab">Survey Builder</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <!-- All Surveys Tab -->
                    <div class="tab-content active" id="all-surveys">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Survey Title</th>
                                        <th>Campaign</th>
                                        <th>Status</th>
                                        <th>Questions</th>
                                        <th>Responses</th>
                                        <th>Response Rate</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($surveys_data)): ?>
                                        <?php foreach ($surveys_data as $survey): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($survey['title']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($survey['description'] ?? '', 0, 50)); ?>...</small>
                                                </td>
                                                <td><?php echo htmlspecialchars($survey['campaign_name'] ?? 'No Campaign'); ?></td>
                                                <td>
                                                    <span class="status-badge <?php echo htmlspecialchars($survey['status']); ?>">
                                                        <?php echo ucfirst(htmlspecialchars($survey['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $survey['question_count']; ?></td>
                                                <td><?php echo $survey['response_count']; ?></td>
                                                <td>
                                                    <?php 
                                                    $sent = $survey['responses_sent'] ?? 100;
                                                    $received = $survey['response_count'];
                                                    $rate = $sent > 0 ? round(($received / $sent) * 100, 1) : 0;
                                                    ?>
                                                    <div class="response-bar">
                                                        <div class="response-fill" style="width: <?php echo $rate; ?>%"></div>
                                                    </div>
                                                    <small><?php echo $rate; ?>%</small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary" onclick="viewSurvey(<?php echo $survey['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-outline-success" onclick="editSurvey(<?php echo $survey['id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-info" onclick="viewResponses(<?php echo $survey['id']; ?>)">
                                                            <i class="fas fa-chart-bar"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger" onclick="deleteSurvey(<?php echo $survey['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <i class="fas fa-poll fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No surveys found. Create your first survey to collect feedback!</p>
                                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newSurveyModal">
                                                    <i class="fas fa-plus"></i> Create Survey
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Active Surveys Tab -->
                    <div class="tab-content" id="active-surveys">
                        <h5>Active Surveys</h5>
                        <p class="text-muted">Surveys currently collecting responses.</p>
                        <!-- Content for active surveys will be loaded here -->
                    </div>

                    <!-- Analytics Tab -->
                    <div class="tab-content" id="survey-analytics">
                        <h5>Survey Analytics</h5>
                        <p class="text-muted">Response analysis and survey performance metrics.</p>
                        <div class="row">
                            <div class="col-md-6">
                                <canvas id="responseRateChart" width="400" height="200"></canvas>
                            </div>
                            <div class="col-md-6">
                                <canvas id="satisfactionChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Survey Builder Tab -->
                    <div class="tab-content" id="survey-builder">
                        <h5>Survey Builder</h5>
                        <p class="text-muted">Create and customize survey questions.</p>
                        <div id="questionBuilder">
                            <div class="row">
                                <div class="col-md-8">
                                    <div id="questionsContainer">
                                        <div class="question-item">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="question-type-badge">Multiple Choice</span>
                                                <button class="btn btn-sm btn-outline-danger" onclick="removeQuestion(this)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                            <input type="text" class="form-control mb-2" placeholder="Enter your question" value="How satisfied are you with our safety campaign?">
                                            <div class="options-container">
                                                <input type="text" class="form-control mb-1" placeholder="Option 1" value="Very Satisfied">
                                                <input type="text" class="form-control mb-1" placeholder="Option 2" value="Satisfied">
                                                <input type="text" class="form-control mb-1" placeholder="Option 3" value="Neutral">
                                                <input type="text" class="form-control mb-1" placeholder="Option 4" value="Dissatisfied">
                                            </div>
                                            <button class="btn btn-sm btn-outline-primary mt-2" onclick="addOption(this)">
                                                <i class="fas fa-plus"></i> Add Option
                                            </button>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary" onclick="addQuestion()">
                                        <i class="fas fa-plus"></i> Add Question
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6>Question Types</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-grid gap-2">
                                                <button class="btn btn-outline-secondary" onclick="addQuestionType('multiple_choice')">
                                                    <i class="fas fa-list"></i> Multiple Choice
                                                </button>
                                                <button class="btn btn-outline-secondary" onclick="addQuestionType('text')">
                                                    <i class="fas fa-font"></i> Text Input
                                                </button>
                                                <button class="btn btn-outline-secondary" onclick="addQuestionType('rating')">
                                                    <i class="fas fa-star"></i> Rating Scale
                                                </button>
                                                <button class="btn btn-outline-secondary" onclick="addQuestionType('yes_no')">
                                                    <i class="fas fa-check"></i> Yes/No
                                                </button>
                                                <button class="btn btn-outline-secondary" onclick="addQuestionType('dropdown')">
                                                    <i class="fas fa-caret-down"></i> Dropdown
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Survey Modal -->
    <div class="modal fade" id="newSurveyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Survey</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="newSurveyForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Survey Title *</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Campaign</label>
                                    <select class="form-control" name="campaign_id">
                                        <option value="">Select Campaign</option>
                                        <!-- Campaign options will be loaded dynamically -->
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="datetime-local" class="form-control" name="start_date">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">End Date</label>
                                    <input type="datetime-local" class="form-control" name="end_date">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Survey Type</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="survey_type" value="feedback" id="type1" checked>
                                        <label class="form-check-label" for="type1">Feedback Survey</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="survey_type" value="assessment" id="type2">
                                        <label class="form-check-label" for="type2">Knowledge Assessment</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="survey_type" value="evaluation" id="type3">
                                        <label class="form-check-label" for="type3">Program Evaluation</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="anonymous" id="anonymous" checked>
                            <label class="form-check-label" for="anonymous">
                                Allow anonymous responses
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Survey</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Tab functionality
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all tabs and content
                document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Show corresponding content
                const targetId = this.getAttribute('href').substring(1);
                const targetContent = document.getElementById(targetId);
                if (targetContent) {
                    targetContent.classList.add('active');
                }
            });
        });

        // Survey form submission
        document.getElementById('newSurveyForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('backend/api/surveys.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error creating survey: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating survey');
            });
        });

        // Survey action functions
        function viewSurvey(id) {
            window.location.href = `survey-details.php?id=${id}`;
        }

        function editSurvey(id) {
            // Implement edit functionality
            console.log('Edit survey:', id);
        }

        function viewResponses(id) {
            window.location.href = `survey-responses.php?id=${id}`;
        }

        function deleteSurvey(id) {
            if (confirm('Are you sure you want to delete this survey?')) {
                fetch(`backend/api/surveys.php?action=delete&id=${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting survey: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting survey');
                });
            }
        }

        // Question builder functions
        function addQuestion() {
            const container = document.getElementById('questionsContainer');
            const questionHtml = `
                <div class="question-item">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="question-type-badge">Text Input</span>
                        <button class="btn btn-sm btn-outline-danger" onclick="removeQuestion(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <input type="text" class="form-control mb-2" placeholder="Enter your question">
                </div>
            `;
            container.insertAdjacentHTML('beforeend', questionHtml);
        }

        function removeQuestion(button) {
            button.closest('.question-item').remove();
        }

        function addOption(button) {
            const container = button.previousElementSibling;
            const optionHtml = `<input type="text" class="form-control mb-1" placeholder="New option">`;
            container.insertAdjacentHTML('beforeend', optionHtml);
        }

        function addQuestionType(type) {
            const container = document.getElementById('questionsContainer');
            let questionHtml = '';
            
            switch(type) {
                case 'multiple_choice':
                    questionHtml = `
                        <div class="question-item">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="question-type-badge">Multiple Choice</span>
                                <button class="btn btn-sm btn-outline-danger" onclick="removeQuestion(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <input type="text" class="form-control mb-2" placeholder="Enter your question">
                            <div class="options-container">
                                <input type="text" class="form-control mb-1" placeholder="Option 1">
                                <input type="text" class="form-control mb-1" placeholder="Option 2">
                            </div>
                            <button class="btn btn-sm btn-outline-primary mt-2" onclick="addOption(this)">
                                <i class="fas fa-plus"></i> Add Option
                            </button>
                        </div>
                    `;
                    break;
                case 'rating':
                    questionHtml = `
                        <div class="question-item">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="question-type-badge">Rating Scale</span>
                                <button class="btn btn-sm btn-outline-danger" onclick="removeQuestion(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <input type="text" class="form-control mb-2" placeholder="Enter your question">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Scale (1-10)</label>
                                    <select class="form-control">
                                        <option value="5">1-5</option>
                                        <option value="10" selected>1-10</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                default:
                    questionHtml = `
                        <div class="question-item">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="question-type-badge">Text Input</span>
                                <button class="btn btn-sm btn-outline-danger" onclick="removeQuestion(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <input type="text" class="form-control mb-2" placeholder="Enter your question">
                        </div>
                    `;
            }
            
            container.insertAdjacentHTML('beforeend', questionHtml);
        }

        // Handle logout
        <?php if (isset($_GET['logout'])): ?>
        <?php
        session_destroy();
        header("Location: login.php");
        exit();
        ?>
        <?php endif; ?>
    </script>
</body>
</html>