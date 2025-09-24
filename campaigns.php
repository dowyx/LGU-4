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

// Get campaigns data
$campaigns_data = [];
try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get all campaigns with statistics
    $query = "SELECT c.*, 
                COUNT(DISTINCT cm.id) as milestones_count,
                COUNT(DISTINCT e.id) as events_count
              FROM campaigns c 
              LEFT JOIN campaign_milestones cm ON c.id = cm.campaign_id 
              LEFT JOIN events e ON c.id = e.campaign_id 
              GROUP BY c.id 
              ORDER BY c.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $campaigns_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Campaigns data error: " . $e->getMessage());
    $campaigns_data = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Management - Public Safety Campaign System</title>
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
        .status-badge.planning { background: #fff3cd; color: #856404; }
        .status-badge.completed { background: #cce5ff; color: #004085; }
        .status-badge.paused { background: #f8d7da; color: #721c24; }
        
        .progress-bar-custom {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="px-3 mb-4">
            <h4><i class="fas fa-shield-alt"></i> Safety Campaign</h4>
        </div>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="campaigns.php" class="active"><i class="fas fa-bullhorn"></i> Campaigns</a>
        <a href="audience.php"><i class="fas fa-users"></i> Audience</a>
        <a href="sms-alerts.php"><i class="fas fa-sms"></i> SMS Alerts</a>
        <a href="surveys.php"><i class="fas fa-poll"></i> Surveys</a>
        <a href="users.php"><i class="fas fa-user-cog"></i> Users</a>
        <div class="border-top mt-3 pt-3">
            <a href="?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">Campaign Management</span>
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
                            <h2>Campaign Management</h2>
                            <p class="text-muted">Create, manage, and track your public safety campaigns</p>
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newCampaignModal">
                            <i class="fas fa-plus"></i> New Campaign
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-bullhorn fa-2x text-primary mb-2"></i>
                            <h3><?php echo count($campaigns_data); ?></h3>
                            <p class="text-muted mb-0">Total Campaigns</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-play fa-2x text-success mb-2"></i>
                            <h3><?php echo count(array_filter($campaigns_data, function($c) { return $c['status'] === 'active'; })); ?></h3>
                            <p class="text-muted mb-0">Active Campaigns</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar fa-2x text-warning mb-2"></i>
                            <h3><?php echo count(array_filter($campaigns_data, function($c) { return $c['status'] === 'planning'; })); ?></h3>
                            <p class="text-muted mb-0">In Planning</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-check fa-2x text-info mb-2"></i>
                            <h3><?php echo count(array_filter($campaigns_data, function($c) { return $c['status'] === 'completed'; })); ?></h3>
                            <p class="text-muted mb-0">Completed</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campaigns Table -->
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" href="#all-campaigns" data-bs-toggle="tab">All Campaigns</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#active-campaigns" data-bs-toggle="tab">Active</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#planning-campaigns" data-bs-toggle="tab">Planning</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#analytics" data-bs-toggle="tab">Analytics</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <!-- All Campaigns Tab -->
                    <div class="tab-content active" id="all-campaigns">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Campaign Name</th>
                                        <th>Status</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Progress</th>
                                        <th>Engagement</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($campaigns_data)): ?>
                                        <?php foreach ($campaigns_data as $campaign): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($campaign['name']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($campaign['primary_objective'] ?? 'General Safety'); ?></small>
                                                </td>
                                                <td>
                                                    <span class="status-badge <?php echo htmlspecialchars($campaign['status']); ?>">
                                                        <?php echo ucfirst(htmlspecialchars($campaign['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $campaign['start_date'] ? date('M d, Y', strtotime($campaign['start_date'])) : 'Not set'; ?></td>
                                                <td><?php echo $campaign['end_date'] ? date('M d, Y', strtotime($campaign['end_date'])) : 'Not set'; ?></td>
                                                <td>
                                                    <div class="progress-bar-custom">
                                                        <div class="progress-fill" style="width: <?php echo $campaign['progress'] ?? 0; ?>%"></div>
                                                    </div>
                                                    <small><?php echo $campaign['progress'] ?? 0; ?>%</small>
                                                </td>
                                                <td><?php echo $campaign['engagement_rate'] ?? 'N/A'; ?>%</td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-primary" onclick="viewCampaign(<?php echo $campaign['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-outline-success" onclick="editCampaign(<?php echo $campaign['id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger" onclick="deleteCampaign(<?php echo $campaign['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                                                <p class="text-muted">No campaigns found. Create your first campaign to get started!</p>
                                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newCampaignModal">
                                                    <i class="fas fa-plus"></i> Create Campaign
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Active Campaigns Tab -->
                    <div class="tab-content" id="active-campaigns">
                        <h5>Active Campaigns</h5>
                        <p class="text-muted">Currently running campaigns that require your attention.</p>
                        <!-- Content for active campaigns will be loaded here -->
                    </div>

                    <!-- Planning Campaigns Tab -->
                    <div class="tab-content" id="planning-campaigns">
                        <h5>Campaigns in Planning</h5>
                        <p class="text-muted">Upcoming campaigns in the planning phase.</p>
                        <!-- Content for planning campaigns will be loaded here -->
                    </div>

                    <!-- Analytics Tab -->
                    <div class="tab-content" id="analytics">
                        <h5>Campaign Analytics</h5>
                        <p class="text-muted">Performance metrics and insights for your campaigns.</p>
                        <div class="row">
                            <div class="col-md-6">
                                <canvas id="campaignPerformanceChart" width="400" height="200"></canvas>
                            </div>
                            <div class="col-md-6">
                                <canvas id="engagementChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Campaign Modal -->
    <div class="modal fade" id="newCampaignModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Campaign</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="newCampaignForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Campaign Name *</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Primary Objective *</label>
                                    <select class="form-control" name="primary_objective" required>
                                        <option value="">Select Objective</option>
                                        <option value="awareness">Awareness</option>
                                        <option value="education">Education</option>
                                        <option value="behavior_change">Behavior Change</option>
                                        <option value="policy_advocacy">Policy Advocacy</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" class="form-control" name="start_date">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">End Date</label>
                                    <input type="date" class="form-control" name="end_date">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Target Audience</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="target_audience[]" value="general_public" id="audience1">
                                        <label class="form-check-label" for="audience1">General Public</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="target_audience[]" value="seniors" id="audience2">
                                        <label class="form-check-label" for="audience2">Senior Citizens</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="target_audience[]" value="parents" id="audience3">
                                        <label class="form-check-label" for="audience3">Parents</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="target_audience[]" value="students" id="audience4">
                                        <label class="form-check-label" for="audience4">Students</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="target_audience[]" value="businesses" id="audience5">
                                        <label class="form-check-label" for="audience5">Businesses</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="target_audience[]" value="drivers" id="audience6">
                                        <label class="form-check-label" for="audience6">Drivers</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Campaign</button>
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

        // Campaign form submission
        document.getElementById('newCampaignForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('backend/api/campaigns.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error creating campaign: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating campaign');
            });
        });

        // Campaign action functions
        function viewCampaign(id) {
            window.location.href = `campaign-details.php?id=${id}`;
        }

        function editCampaign(id) {
            // Implement edit functionality
            console.log('Edit campaign:', id);
        }

        function deleteCampaign(id) {
            if (confirm('Are you sure you want to delete this campaign?')) {
                fetch(`backend/api/campaigns.php?action=delete&id=${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error deleting campaign: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting campaign');
                });
            }
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