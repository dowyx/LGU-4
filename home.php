<?php
/**
 * Home Dashboard - Main Application Interface
 * Public Safety Campaign Management System
 */

session_start();

// Include required files
require_once 'backend/config/database.php';
require_once 'backend/utils/helpers.php';

// Handle logout if requested
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    // Clear all session data
    session_unset();
    session_destroy();
    
    // Clear any previous output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// Check if user is logged in - more robust validation with debugging
$logged_in = false;
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // Additional checks for other required session variables
    if (isset($_SESSION['user_name']) && !empty($_SESSION['user_name'])) {
        $logged_in = true;
    } else {
        error_log("Session validation failed: user_name not set or empty");
    }
} else {
    error_log("Session validation failed: user_id not set or empty");
    error_log("Current session data: " . print_r($_SESSION, true));
}

if (!$logged_in) {
    // Clear any previous output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    // Store the current URL for redirecting back after login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Check for successful login
$login_success = false;
if (isset($_SESSION['login_success']) && $_SESSION['login_success'] === true) {
    $login_success = true;
    // Clear the flag so it doesn't show again on refresh
    unset($_SESSION['login_success']);
}

// Get user data from session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';
$user_email = $_SESSION['user_email'] ?? '';
$user_role = $_SESSION['user_role'] ?? 'User';
$user_avatar = $_SESSION['user_avatar'] ?? strtoupper(substr($user_name, 0, 2));

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Fetch additional user data if needed
try {
    $query = "SELECT first_name, last_name, email, role, department, phone, location FROM users WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    
    if ($stmt->rowCount() > 0) {
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Update session with fresh data
        $_SESSION['user_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
        $_SESSION['user_email'] = $user_data['email'];
        $_SESSION['user_role'] = $user_data['role'];
        
        $user_name = $_SESSION['user_name'];
        $user_email = $_SESSION['user_email'];
        $user_role = $_SESSION['user_role'];
        $user_department = $user_data['department'] ?? '';
        $user_phone = $user_data['phone'] ?? '';
        $user_location = $user_data['location'] ?? '';
        
        // Create user data array for JavaScript synchronization
        $js_user_data = [
            'id' => $user_id,
            'first_name' => $user_data['first_name'],
            'last_name' => $user_data['last_name'],
            'email' => $user_data['email'],
            'role' => $user_data['role'],
            'department' => $user_data['department'] ?? '',
            'phone' => $user_data['phone'] ?? '',
            'location' => $user_data['location'] ?? '',
            'name' => $user_data['first_name'] . ' ' . $user_data['last_name']
        ];
        
        // Store user data in session for JavaScript access
        $_SESSION['js_user_data'] = json_encode($js_user_data);
    }
} catch (Exception $e) {
    error_log("Error fetching user data: " . $e->getMessage());
}

// Fetch dashboard statistics
$stats = [
    'active_campaigns' => 0,
    'upcoming_events' => 0,
    'content_items' => 0,
    'recent_responses' => 0
];

// Fetch recent campaigns data
$recent_campaigns = [];

try {
    $stats_query = "SELECT 
                        (SELECT COUNT(*) FROM campaigns WHERE status = 'active') as active_campaigns,
                        (SELECT COUNT(*) FROM events WHERE event_date >= CURDATE()) as upcoming_events,
                        (SELECT COUNT(*) FROM content_items) as content_items,
                        (SELECT COUNT(*) FROM survey_responses WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as recent_responses";
    
    $stats_stmt = $conn->prepare($stats_query);
    $stats_stmt->execute();
    $db_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($db_stats) {
        $stats = $db_stats;
    }
    
    // Fetch recent campaigns
    $campaigns_query = "SELECT id, name, status, start_date, end_date, progress, engagement_rate, created_at
                        FROM campaigns 
                        ORDER BY created_at DESC 
                        LIMIT 5";
    
    $campaigns_stmt = $conn->prepare($campaigns_query);
    $campaigns_stmt->execute();
    $recent_campaigns = $campaigns_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error fetching dashboard data: " . $e->getMessage());
    // Use default values if database query fails
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Safety Campaign Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-brand">
                <h1 class="navbar-title">Public Safety Campaign</h1>
                <button class="menu-toggle" id="menuToggle" style="display: none;">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <div class="navbar-user">
                <div class="ai-chatbot-icon" id="chatbotToggle">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="notification-icon" id="notificationIcon">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="notificationBadge">3</span>
                </div>
                <div class="user-info" id="userProfileToggle">
                    <div class="user-avatar" id="navbarUserAvatar"><?php echo htmlspecialchars($user_avatar); ?></div>
                    <span id="navbarUserName"><?php echo htmlspecialchars($user_name); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </div>
    </nav>

    <!-- Notification Panel -->
    <div class="notification-panel" id="notificationPanel">
        <div class="notification-header">
            <h3>Notifications</h3>
            <button class="btn-text" id="markAllRead">Mark all as read</button>
        </div>
        <div class="notification-list" id="notificationList">
            <!-- Notifications will be dynamically added here -->
        </div>
        <div class="notification-footer">
            <a href="#" class="btn-text">View all notifications</a>
        </div>
    </div>

    <!-- User Profile Dropdown -->
    <div class="profile-dropdown" id="profileDropdown">
        <div class="profile-header">
            <div class="user-avatar large" id="dropdownUserAvatar"><?php echo htmlspecialchars($user_avatar); ?></div>
            <div class="user-details">
                <h3 id="userName"><?php echo htmlspecialchars($user_name); ?></h3>
                <p id="userRole"><?php echo htmlspecialchars($user_role); ?></p>
                <p class="user-email" id="userEmail"><?php echo htmlspecialchars($user_email); ?></p>
            </div>
        </div>
        <div class="profile-menu">
            <a href="#" class="profile-menu-item" data-action="view-profile">
                <i class="fas fa-user"></i>
                <span>View Profile</span>
            </a>
            <a href="#" class="profile-menu-item" data-action="edit-profile">
                <i class="fas fa-edit"></i>
                <span>Edit Profile</span>
            </a>
            <a href="#" class="profile-menu-item" data-action="settings">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            <hr class="profile-menu-divider">
            <a href="#" class="profile-menu-item" data-action="logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <!-- Notification Overlay -->
    <div class="notification-overlay" id="notificationOverlay"></div>



<!-- AI Chatbot Modal -->
<div class="chatbot-modal" id="chatbotModal">
    <div class="chatbot-container">
        <div class="chatbot-header">
            <div class="chatbot-title">
                <i class="fas fa-robot"></i>
                <span>Safety Assistant</span>
            </div>
            <div class="chatbot-controls">
                <button class="chatbot-minimize" id="minimizeChatbot">
                    <i class="fas fa-minus"></i>
                </button>
                <button class="chatbot-close" id="closeChatbot">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="chatbot-body" id="chatbotBody">
            <div class="chat-message bot-message">
                <div class="message-avatar bot-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="message-content">
                    <div class="message-text">Hello! I'm your Safety Assistant. I can help you with:
                    <ul>
                        <li>Campaign planning and strategies</li>
                        <li>Content creation suggestions</li>
                        <li>Audience engagement tips</li>
                        <li>Safety information and best practices</li>
                        <li>Impact measurement guidance</li>
                    </ul>
                    What would you like to know?</div>
                    <div class="message-time">Just now</div>
                </div>
            </div>
        </div>
        <div class="chatbot-input-area">
            <div class="quick-actions">
                <button class="quick-action-btn" data-question="How do I create an effective safety campaign?">
                    <i class="fas fa-bullhorn"></i>
                    Campaign Tips
                </button>
                <button class="quick-action-btn" data-question="What are the best practices for audience engagement?">
                    <i class="fas fa-users"></i>
                    Engagement
                </button>
                <button class="quick-action-btn" data-question="How can I measure campaign impact?">
                    <i class="fas fa-chart-line"></i>
                    Impact Metrics
                </button>
            </div>
            <div class="chat-input-container">
                <input type="text" id="chatInput" placeholder="Ask me about safety campaigns, content creation, or best practices..." maxlength="500">
                <button id="sendChatMessage">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="typing-indicator" id="typingIndicator" style="display: none;">
                <div class="typing-dots">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <span>Safety Assistant is typing...</span>
            </div>
        </div>
    </div>
</div>



    <!-- User Profile Modal -->
    <div class="profile-modal" id="profileModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="profileModalTitle">User Profile</h3>
                <button class="modal-close" id="profileModalClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="profileModalBody">
                <!-- Profile Avatar Section -->
                <div class="profile-avatar-section">
                    <div class="profile-avatar-large" id="profileAvatarLarge"><?php echo htmlspecialchars($user_avatar); ?></div>
                    <h3 id="profileFullName"><?php echo htmlspecialchars($user_name); ?></h3>
                    <p id="profileRoleDisplay"><?php echo htmlspecialchars($user_role); ?></p>
                    <p class="user-email" id="userEmail"><?php echo htmlspecialchars($user_email); ?></p>
                </div>
                
                <!-- Profile Form -->
                <form id="profileForm">
                    <div class="profile-form-grid">
                        <div class="form-group">
                            <label class="form-label" for="firstName">First Name</label>
                            <input type="text" id="firstName" name="firstName" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="lastName">Last Name</label>
                            <input type="text" id="lastName" name="lastName" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-input">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="role">Role</label>
                            <select id="role" name="role" class="form-input">
                                <option value="Campaign Manager">Campaign Manager</option>
                                <option value="Content Creator">Content Creator</option>
                                <option value="Data Analyst">Data Analyst</option>
                                <option value="Community Coordinator">Community Coordinator</option>
                                <option value="Administrator">Administrator</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="department">Department</label>
                            <input type="text" id="department" name="department" class="form-input">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="location">Location</label>
                            <input type="text" id="location" name="location" class="form-input">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="timezone">Timezone</label>
                            <select id="timezone" name="timezone" class="form-input">
                                <option value="Eastern Time (ET)">Eastern Time (ET)</option>
                                <option value="Central Time (CT)">Central Time (CT)</option>
                                <option value="Mountain Time (MT)">Mountain Time (MT)</option>
                                <option value="Pacific Time (PT)">Pacific Time (PT)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="language">Language</label>
                            <select id="language" name="language" class="form-input">
                                <option value="English">English</option>
                                <option value="Spanish">Spanish</option>
                                <option value="French">French</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Notification Preferences -->
                    <div class="profile-preferences">
                        <h4>Notification Preferences</h4>
                        <div class="preference-group">
                            <div class="preference-item">
                                <input type="checkbox" id="emailNotifications" name="emailNotifications" checked>
                                <span>Email Notifications</span>
                            </div>
                            <div class="preference-item">
                                <input type="checkbox" id="pushNotifications" name="pushNotifications" checked>
                                <span>Push Notifications</span>
                            </div>
                            <div class="preference-item">
                                <input type="checkbox" id="smsNotifications" name="smsNotifications">
                                <span>SMS Notifications</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" id="cancelProfileEdit">Cancel</button>
                        <button type="submit" class="btn-primary" id="saveProfileBtn" style="display: none;">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="main-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-modules">
                <h2 class="sidebar-title">Modules</h2>
                <ul class="module-list">
                    <li><a href="#" class="module-link active" data-module="dashboard"><i class="fas fa-chart-line"></i><span>Dashboard</span></a></li>
                    <li><a href="#" class="module-link" data-module="planning"><i class="fas fa-calendar-alt"></i><span>Campaign Planning</span></a></li>
                    <li><a href="#" class="module-link" data-module="content"><i class="fas fa-file-video"></i><span>Content Repository</span></a></li>
                    <li><a href="#" class="module-link" data-module="audience"><i class="fas fa-users"></i><span>Audience Segmentation</span></a></li>
                    <li><a href="#" class="module-link" data-module="events"><i class="fas fa-microphone"></i><span>Event Management</span></a></li>
                    <li><a href="#" class="module-link" data-module="feedback"><i class="fas fa-comment-dots"></i><span>Feedback & Surveys</span></a></li>
                    <li><a href="#" class="module-link" data-module="monitoring"><i class="fas fa-chart-bar"></i><span>Impact Monitoring</span></a></li>
                    <li><a href="#" class="module-link" data-module="collaboration"><i class="fas fa-handshake"></i><span>Collaboration</span></a></li>
                </ul>
            </div>
            
            <div class="sidebar-campaigns">
                <h2 class="sidebar-title">Recent Campaigns</h2>
                <ul class="campaign-list">
                    <li class="campaign-item">
                        <a href="#" class="campaign-link">
                            <span>Road Safety</span>
                            <span class="status-badge active">Active</span>
                        </a>
                    </li>
                    <li class="campaign-item">
                        <a href="#" class="campaign-link">
                            <span>Cyber Security</span>
                            <span class="status-badge planning">Planning</span>
                        </a>
                    </li>
                    <li class="campaign-item">
                        <a href="#" class="campaign-link">
                            <span>Fire Prevention</span>
                            <span class="status-badge completed">Completed</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content">
            <!-- Dashboard Module -->
            <div id="dashboard" class="module-section active-module">
                <div class="page-header">
                    <div class="page-header-content">
                        <h2 class="page-title">Dashboard</h2>
                        <p class="page-description">Overview of your public safety campaigns and their performance</p>
                    </div>
                    <div class="page-header-controls">
                        <div class="dashboard-controls">
                            <!-- Controls will be added dynamically -->
                        </div>
                        <div class="dashboard-meta">
                            <!-- Meta info will be added dynamically -->
                        </div>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <p class="stat-label">Active Campaigns</p>
                                <h3 class="stat-value"><?php echo number_format($stats['active_campaigns'] ?? 5); ?></h3>
                                <p class="stat-description">Currently running</p>
                            </div>
                            <div class="stat-icon blue">
                                <i class="fas fa-running"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <p class="stat-label">Upcoming Events</p>
                                <h3 class="stat-value"><?php echo number_format($stats['upcoming_events'] ?? 12); ?></h3>
                                <p class="stat-description">Scheduled ahead</p>
                            </div>
                            <div class="stat-icon green">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <p class="stat-label">Content Items</p>
                                <h3 class="stat-value"><?php echo number_format($stats['content_items'] ?? 47); ?></h3>
                                <p class="stat-description">Available resources</p>
                            </div>
                            <div class="stat-icon purple">
                                <i class="fas fa-file-video"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-content">
                            <div class="stat-info">
                                <p class="stat-label">Survey Responses</p>
                                <h3 class="stat-value"><?php echo number_format($stats['recent_responses'] ?? 1243); ?></h3>
                                <p class="stat-description">Last 30 days</p>
                            </div>
                            <div class="stat-icon yellow">
                                <i class="fas fa-comment-dots"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Dashboard Charts Section -->
                <div class="dashboard-content">
                    <div class="dashboard-card large">
                        <div class="card-header">
                            <h3 class="card-title">Campaign Performance</h3>
                            <select class="time-range-selector form-select">
                                <option value="7d">Last 7 days</option>
                                <option value="30d" selected>Last 30 days</option>
                                <option value="90d">Last 90 days</option>
                                <option value="1y">Last year</option>
                            </select>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="campaignPerformanceChart"></canvas>
                        </div>
                    </div>

                    <div class="dashboard-card large">
                        <div class="card-header">
                            <h3 class="card-title">Audience Engagement</h3>
                            <select class="time-range-selector form-select">
                                <option value="7d">Last 7 days</option>
                                <option value="30d" selected>Last 30 days</option>
                                <option value="90d">Last 90 days</option>
                                <option value="1y">Last year</option>
                            </select>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="audienceEngagementChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Secondary Dashboard Content -->
                <div class="dashboard-content">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Campaigns</h3>
                            <div class="card-actions">
                                <button class="btn-secondary btn-sm" onclick="dashboard.refreshCampaignTable()">
                                    <i class="fas fa-sync-alt"></i>
                                    Refresh
                                </button>
                            </div>
                        </div>
                        <div class="table-container">
                            <table class="campaigns-table">
                                <thead>
                                    <tr>
                                        <th>Campaign</th>
                                        <th>Status</th>
                                        <th>Progress</th>
                                        <th>Engagement</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($recent_campaigns)): ?>
                                        <?php foreach ($recent_campaigns as $campaign): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($campaign['name']); ?></td>
                                                <td><span class="status-badge <?php echo htmlspecialchars($campaign['status']); ?>"><?php echo ucfirst(htmlspecialchars($campaign['status'])); ?></span></td>
                                                <td>
                                                    <div class="progress-bar">
                                                        <div class="progress-fill" style="width: <?php echo $campaign['progress'] ?? 0; ?>%"></div>
                                                    </div>
                                                    <small class="progress-text"><?php echo $campaign['progress'] ?? 0; ?>%</small>
                                                </td>
                                                <td><?php echo $campaign['engagement_rate'] ?? 'N/A'; ?>%</td>
                                                <td>
                                                    <button class="btn-icon blue" onclick="dashboard.viewCampaignDetails(<?php echo $campaign['id']; ?>)" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn-icon green" onclick="dashboard.exportCampaignData(<?php echo $campaign['id']; ?>)" title="Export Data">
                                                        <i class="fas fa-download"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td>Road Safety Awareness</td>
                                            <td><span class="status-badge active">Active</span></td>
                                            <td>
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: 75%"></div>
                                                </div>
                                                <small class="progress-text">75%</small>
                                            </td>
                                            <td>68%</td>
                                            <td>
                                                <button class="btn-icon blue" onclick="dashboard.viewCampaignDetails(1)" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn-icon green" onclick="dashboard.exportCampaignData(1)" title="Export Data">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Cybersecurity for Seniors</td>
                                            <td><span class="status-badge planning">Planning</span></td>
                                            <td>
                                                <div class="progress-bar">
                                                    <div class="progress-fill blue" style="width: 30%"></div>
                                                </div>
                                                <small class="progress-text">30%</small>
                                            </td>
                                            <td>N/A</td>
                                            <td>
                                                <button class="btn-icon blue" onclick="dashboard.viewCampaignDetails(2)" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn-icon green" onclick="dashboard.exportCampaignData(2)" title="Export Data">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Disaster Preparedness</td>
                                            <td><span class="status-badge draft">Draft</span></td>
                                            <td>
                                                <div class="progress-bar">
                                                    <div class="progress-fill yellow" style="width: 15%"></div>
                                                </div>
                                                <small class="progress-text">15%</small>
                                            </td>
                                            <td>N/A</td>
                                            <td>
                                                <button class="btn-icon blue" onclick="dashboard.viewCampaignDetails(3)" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn-icon green" onclick="dashboard.exportCampaignData(3)" title="Export Data">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3 class="card-title">Upcoming Events</h3>
                            <div class="card-actions">
                                <a href="#" class="btn-text" onclick="moduleManager.switchToModule('events')">
                                    View All Events
                                </a>
                            </div>
                        </div>
                        <div class="events-list">
                            <div class="event-item">
                                <div class="event-date red">
                                    <div class="event-day">15</div>
                                    <div class="event-month">OCT</div>
                                </div>
                                <div class="event-details">
                                    <h4 class="event-title">Road Safety Workshop</h4>
                                    <p class="event-info">City Community Center, 10:00 AM</p>
                                    <p class="event-info">45 registered • Part of Road Safety Campaign</p>
                                </div>
                                <div class="event-actions">
                                    <button class="btn-icon blue" onclick="dashboard.viewEventDetails(1)" title="View Details">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="event-item">
                                <div class="event-date blue">
                                    <div class="event-day">22</div>
                                    <div class="event-month">OCT</div>
                                </div>
                                <div class="event-details">
                                    <h4 class="event-title">Cyber Safety Seminar</h4>
                                    <p class="event-info">Central Library, 2:00 PM</p>
                                    <p class="event-info">32 registered • Part of Cyber Security Campaign</p>
                                </div>
                                <div class="event-actions">
                                    <button class="btn-icon blue" onclick="dashboard.viewEventDetails(2)" title="View Details">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="event-item">
                                <div class="event-date green">
                                    <div class="event-day">05</div>
                                    <div class="event-month">NOV</div>
                                </div>
                                <div class="event-details">
                                    <h4 class="event-title">Emergency Response Training</h4>
                                    <p class="event-info">Fire Station #3, 9:00 AM</p>
                                    <p class="event-info">28 registered • Part of Disaster Preparedness</p>
                                </div>
                                <div class="event-actions">
                                    <button class="btn-icon blue" onclick="dashboard.viewEventDetails(3)" title="View Details">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Performance Section -->
                <div class="dashboard-content single-column">
                    <div class="dashboard-card large">
                        <div class="card-header">
                            <h3 class="card-title">Content Performance</h3>
                            <div class="card-actions">
                                <select class="time-range-selector form-select">
                                    <option value="7d">Last 7 days</option>
                                    <option value="30d" selected>Last 30 days</option>
                                    <option value="90d">Last 90 days</option>
                                    <option value="1y">Last year</option>
                                </select>
                                <button class="btn-secondary btn-sm" onclick="dashboard.exportContentData()">
                                    <i class="fas fa-download"></i>
                                    Export
                                </button>
                            </div>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="contentPerformanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campaign Planning Module -->
            <div id="planning" class="module-section">
                <div class="page-header">
                    <h2 class="page-title">Campaign Planning & Scheduling</h2>
                    <p class="page-description">Create and manage your public safety campaigns</p>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Current Campaigns</h3>
                        <button class="btn-primary" id="newCampaignBtn">
                            <i class="fas fa-plus"></i>
                            <span>New Campaign</span>
                        </button>
                    </div>

                    <div class="table-container">
                        <table class="campaigns-table">
                            <thead>
                                <tr>
                                    <th>Campaign Name</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Engagement</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_campaigns)): ?>
                                    <?php foreach ($recent_campaigns as $campaign): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($campaign['name']); ?></td>
                                            <td><?php echo $campaign['start_date'] ? date('M d, Y', strtotime($campaign['start_date'])) : 'Not set'; ?></td>
                                            <td><?php echo $campaign['end_date'] ? date('M d, Y', strtotime($campaign['end_date'])) : 'Not set'; ?></td>
                                            <td><span class="status-badge <?php echo htmlspecialchars($campaign['status']); ?>"><?php echo ucfirst(htmlspecialchars($campaign['status'])); ?></span></td>
                                            <td><?php echo $campaign['engagement_rate'] ?? 'N/A'; ?>%</td>
                                            <td>
                                                <button class="btn-icon blue"><i class="fas fa-edit"></i></button>
                                                <button class="btn-icon red"><i class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td>Road Safety Awareness</td>
                                        <td>Oct 1, 2023</td>
                                        <td>Dec 15, 2023</td>
                                        <td><span class="status-badge active">Active</span></td>
                                        <td>68%</td>
                                        <td>
                                            <button class="btn-icon blue"><i class="fas fa-edit"></i></button>
                                            <button class="btn-icon red"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Cybersecurity for Seniors</td>
                                        <td>Nov 1, 2023</td>
                                        <td>Jan 30, 2024</td>
                                        <td><span class="status-badge planning">Planning</span></td>
                                        <td>N/A</td>
                                        <td>
                                            <button class="btn-icon blue"><i class="fas fa-edit"></i></button>
                                            <button class="btn-icon red"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Disaster Preparedness</td>
                                        <td>Jan 15, 2024</td>
                                        <td>Mar 15, 2024</td>
                                        <td><span class="status-badge draft">Draft</span></td>
                                        <td>N/A</td>
                                        <td>
                                            <button class="btn-icon blue"><i class="fas fa-edit"></i></button>
                                            <button class="btn-icon red"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <h3 class="card-title">Create New Campaign</h3>
                    <form class="campaign-form" id="campaignForm">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Campaign Name</label>
                                <input type="text" class="form-input" id="campaignName" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Primary Objective</label>
                                <select class="form-input" id="campaignObjective" required>
                                    <option value="">Select Objective</option>
                                    <option value="awareness">Awareness</option>
                                    <option value="education">Education</option>
                                    <option value="behavior">Behavior Change</option>
                                    <option value="policy">Policy Advocacy</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-input" id="campaignStartDate" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-input" id="campaignEndDate" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Campaign Description</label>
                            <textarea class="form-input" id="campaignDescription" rows="4" required></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Target Audience</label>
                            <select class="form-input" id="campaignAudience" multiple>
                                <option value="general">General Public</option>
                                <option value="seniors">Senior Citizens</option>
                                <option value="parents">Parents with Children</option>
                                <option value="students">School Students</option>
                                <option value="drivers">Drivers</option>
                                <option value="businesses">Local Businesses</option>
                            </select>
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn-secondary" id="cancelCampaignBtn">Cancel</button>
                            <button type="submit" class="btn-primary">Save Campaign</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <h3 class="card-title">Campaign Timeline</h3>
                    <div class="chart-wrapper">
                        <canvas id="campaignTimelineChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Content Repository Module -->
            <div id="content" class="module-section">
                <div class="page-header">
                    <h2 class="page-title">Content Repository</h2>
                    <p class="page-description">Manage all your campaign materials in one place</p>
                </div>

                <div class="content-dashboard-layout">
                    <!-- Sidebar for Content Module -->
                    <aside class="content-sidebar">
                        <div class="nav-item active" data-content-section="dashboard">Dashboard</div>
                        <div class="nav-item" data-content-section="assets">All Assets</div>
                        <div class="nav-item" data-content-section="upload">Upload New</div>
                        
                        <div class="category-list">
                            <h3>Categories</h3>
                            <div class="category-item active" data-content-category="all">All Content</div>
                            <div class="category-item" data-content-category="posters">Posters</div>
                            <div class="category-item" data-content-category="videos">Videos</div>
                            <div class="category-item" data-content-category="guidelines">Guidelines</div>
                            <div class="category-item" data-content-category="social">Social Media</div>
                        </div>
                        
                        <div class="mt-20">
                            <h3>Recent Activity</h3>
                            <div class="version-list sidebar-version-list">
                                <div>Updated Road Safety Poster - Oct 20</div>
                                <div>Uploaded Fire Safety Video - Oct 18</div>
                                <div>Created Cyber Safety Guidelines - Oct 15</div>
                            </div>
                        </div>

                        <div class="mt-20">
                            <h3>Connected Campaigns</h3>
                            <div class="campaign-connection">
                                <h4>Road Safety Campaign</h4>
                                <p>12 content items • 85% completion</p>
                            </div>
                            <div class="campaign-connection">
                                <h4>Cyber Security Campaign</h4>
                                <p>8 content items • 60% completion</p>
                            </div>
                        </div>
                    </aside>
                    
                    <!-- Main Content for Content Module -->
                    <main class="content-main">
                        <!-- Dashboard View -->
                        <section id="content-section-dashboard">
                            <h1 class="section-title">Content Dashboard Overview</h1>
                            
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <div class="stat-info">
                                            <p class="stat-label">Total Content Items</p>
                                            <h3 class="stat-value">47</h3>
                                        </div>
                                        <div class="stat-icon blue">
                                            <i class="fas fa-file"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <div class="stat-info">
                                            <p class="stat-label">Most Viewed</p>
                                            <h3 class="stat-value">12.4K</h3>
                                            <p class="stat-description">Road Safety Video</p>
                                        </div>
                                        <div class="stat-icon green">
                                            <i class="fas fa-eye"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <div class="stat-info">
                                            <p class="stat-label">Engagement Rate</p>
                                            <h3 class="stat-value">68%</h3>
                                        </div>
                                        <div class="stat-icon purple">
                                            <i class="fas fa-chart-line"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="search-container">
                                <input type="text" class="search-input" id="dashboard-search-input" placeholder="Search content...">
                                <select class="filter-select" id="dashboard-filter-select">
                                    <option value="all">All Types</option>
                                    <option value="poster">Posters</option>
                                    <option value="video">Videos</option>
                                    <option value="document">Documents</option>
                                </select>
                                <button class="btn btn-primary" id="dashboard-search-button">Search</button>
                            </div>
                            
                            <div class="chart-card">
                                <div class="chart-header">
                                    <h3 class="chart-title">Content Performance by Type</h3>
                                    <div class="chart-actions">
                                        <select class="form-select form-select-sm">
                                            <option>Last 7 days</option>
                                            <option>Last 30 days</option>
                                            <option>Last 90 days</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="chart-wrapper">
                                    <canvas id="contentTypeChart"></canvas>
                                </div>
                            </div>
                            
                            <h2>Recent Content</h2>
                            <div class="content-grid" id="dashboard-content-grid">
                                <div class="card">
                                    <h4>Road Safety Poster</h4>
                                    <p>Category: Posters</p>
                                    <p>Updated: Oct 20, 2023</p>
                                    <p>Used in: Road Safety Campaign</p>
                                    <span class="status-badge completed">Approved</span>
                                </div>
                                <div class="card">
                                    <h4>Fire Safety Video</h4>
                                    <p>Category: Videos</p>
                                    <p>Uploaded: Oct 18, 2023</p>
                                    <p>Used in: Fire Prevention Campaign</p>
                                    <span class="status-badge active">In Review</span>
                                </div>
                                <div class="card">
                                    <h4>Cyber Safety Guidelines</h4>
                                    <p>Category: Guidelines</p>
                                    <p>Created: Oct 15, 2023</p>
                                    <p>Used in: Cyber Security Campaign</p>
                                    <span class="status-badge completed">Published</span>
                                </div>
                            </div>
                            
                            <h2>Version History</h2>
                            <div class="version-list" id="dashboard-version-history">
                                <div>
                                    <strong>Road Safety Poster v3</strong>
                                    <p>Updated by: John Doe - Oct 20, 2023</p>
                                    <p>Changes: Added new statistics</p>
                                </div>
                                <div>
                                    <strong>Fire Safety Video v1</strong>
                                    <p>Uploaded by: Jane Smith - Oct 18, 2023</p>
                                    <p>Duration: 2:30 • Resolution: 1080p</p>
                                </div>
                                <div>
                                    <strong>Cyber Safety Guidelines v2</strong>
                                    <p>Published by: Mike Johnson - Oct 15, 2023</p>
                                    <p>Added phishing prevention section</p>
                                </div>
                            </div>
                        </section>
                        
                        <!-- All Assets View -->
                        <section id="content-section-assets" class="hidden">
                            <h1 class="section-title">All Assets</h1>
                            
                            <div class="search-container">
                                <input type="text" class="search-input" id="assets-search-input" placeholder="Search assets...">
                                <select class="filter-select" id="assets-filter-select">
                                    <option value="all">All Categories</option>
                                    <option value="posters">Posters</option>
                                    <option value="videos">Videos</option>
                                    <option value="guidelines">Guidelines</option>
                                    <option value="social">Social Media</option>
                                </select>
                                <button class="btn btn-primary" id="assets-filter-button">Filter</button>
                            </div>
                            
                            <div class="content-grid" id="assets-content-grid">
                                <div class="card">
                                    <h4>Road Safety Poster</h4>
                                    <p>Category: Posters</p>
                                    <p>Size: 2.4 MB • Format: PDF</p>
                                    <p>Used in: Road Safety Campaign</p>
                                    <span class="status-badge completed">Approved</span>
                                </div>
                                <div class="card">
                                    <h4>Fire Safety Video</h4>
                                    <p>Category: Videos</p>
                                    <p>Size: 45 MB • Format: MP4</p>
                                    <p>Used in: Fire Prevention Campaign</p>
                                    <span class="status-badge active">In Review</span>
                                </div>
                                <div class="card">
                                    <h4>Cyber Safety Guidelines</h4>
                                    <p>Category: Guidelines</p>
                                    <p>Size: 3.1 MB • Format: DOCX</p>
                                    <p>Used in: Cyber Security Campaign</p>
                                    <span class="status-badge completed">Published</span>
                                </div>
                                <div class="card">
                                    <h4>Safety Social Media Post</h4>
                                    <p>Category: Social Media</p>
                                    <p>Size: 1.2 MB • Format: JPG</p>
                                    <p>Used in: Road Safety Campaign</p>
                                    <span class="status-badge planning">Draft</span>
                                </div>
                            </div>
                        </section>
                        
                        <!-- Upload New View -->
                        <section id="content-section-upload" class="hidden">
                            <h1 class="section-title">Upload New Content</h1>
                            
                            <form id="content-upload-form">
                                <div class="form-group">
                                    <label class="form-label">Title</label>
                                    <input type="text" class="form-input" name="title" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-textarea" name="description" required></textarea>
                                
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Category</label>
                                    <select class="form-select" name="category" required>
                                        <option value="">Select category</option>
                                        <option value="posters">Poster</option>
                                        <option value="videos">Video</option>
                                        <option value="guidelines">Guideline</option>
                                        <option value="social">Social Media</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Associated Campaign</label>
                                    <select class="form-select" name="campaign" required>
                                        <option value="">Select campaign</option>
                                        <option value="road-safety">Road Safety Campaign</option>
                                        <option value="cyber-security">Cyber Security Campaign</option>
                                        <option value="fire-prevention">Fire Prevention Campaign</option>
                                        <option value="disaster-preparedness">Disaster Preparedness</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Tags (comma separated)</label>
                                    <input type="text" class="form-input" name="tags" placeholder="safety, prevention, covid">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Upload File</label>
                                    <div class="file-upload-area" id="file-upload-area">
                                        <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; margin-bottom: 1rem; color: var(--primary-color);"></i>
                                        <p>Drag & drop files here or click to browse</p>
                                        <input type="file" style="display: none;" id="file-input">
                                    </div>
                                    <div class="file-list" id="file-list">
                                        <!-- Files will be listed here -->
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Version Notes</label>
                                    <textarea class="form-textarea" name="version_notes" required></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Upload Content</button>
                            </form>
                        </section>
                    </main>
                </div>
            </div>

            <!-- Audience Segmentation Module -->
            <div id="audience" class="module-section">
                <div class="page-header">
                    <h2 class="page-title">Audience Segmentation</h2>
                    <p class="page-description">Manage your audience segments and outreach campaigns</p>
                </div>
                
                <div class="content-dashboard-layout">
                    <!-- Sidebar for Audience Module -->
                    <aside class="content-sidebar">
                        <div class="nav-item" data-section="profiling">
                            <i class="fas fa-user-friends"></i>
                            <span>Audience Profiling</span>
                        </div>
                        <div class="nav-item" data-section="segmentation">
                            <i class="fas fa-chart-pie"></i>
                            <span>Segmentation Tools</span>
                        </div>
                        <div class="nav-item" data-section="personal-outreach">
                            <i class="fas fa-comment-alt"></i>
                            <span>Personal Outreach</span>
                        </div>
                        <div class="nav-item active" data-section="sms-alerts">
                            <i class="fas fa-sms"></i>
                            <span>SMS Alert Notifications</span>
                        </div>
                        <div class="nav-item" data-section="lists">
                            <i class="fas fa-list"></i>
                            <span>Audience Lists</span>
                        </div>
                        
                        <div class="stats">
                            <h3>Audience Stats</h3>
                            <div class="stat-item">
                                <span>Total Segments</span>
                                <span>12</span>
                            </div>
                            <div class="stat-item">
                                <span>Active Campaigns</span>
                                <span>4</span>
                            </div>
                            <div class="stat-item">
                                <span>Avg. Engagement</span>
                                <span>24.7%</span>
                            </div>
                        </div>
                        
                        <div class="mt-20">
                            <h3>Quick Actions</h3>
                            <button class="btn-primary" id="newAudienceProfileBtn">
                                <i class="fas fa-plus"></i>
                                <span>New Audience Profile</span>
                            </button>
                            <button class="btn-secondary">
                                <i class="fas fa-chart-bar"></i>
                                <span>Generate Report</span>
                            </button>
                        </div>
                    </aside>
                    
                    <!-- Main Content for Audience Module -->
                    <main class="content-main">
                        <!-- Audience Profiling Section -->
                        <section id="audience-section-profiling" class="hidden">
                            <h1 class="section-title">Audience Profiling</h1>
                            
                            <div class="two-column">
                                <div class="card">
                                    <div class="card-title">Create New Profile</div>
                                    <form id="profile-form">
                                        <div class="form-group">
                                            <label class="form-label">Profile Name</label>
                                            <input type="text" class="form-input" placeholder="e.g., Urban Safety Advocates" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-textarea" placeholder="Describe this audience segment..." required></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Primary Safety Concern</label>
                                            <select class="form-select" required>
                                                <option value="">Select concern</option>
                                                <option value="fire">Fire Safety</option>
                                                <option value="cyber">Cybersecurity</option>
                                                <option value="health">Public Health</option>
                                                <option value="traffic">Road Safety</option>
                                                <option value="emergency">Emergency Preparedness</option>
                                            </select>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">Create Profile</button>
                                    </form>
                                </div>
                                
                                <div class="card">
                                    <div class="card-title">Demographic Distribution</div>
                                    <div class="chart-wrapper">
                                        <canvas id="demographic-chart"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mt-20">
                                <div class="card-title">Existing Audience Profiles</div>
                                <table style="width: 100%; border-collapse: collapse;">
                                    <thead>
                                        <tr style="border-bottom: 1px solid var(--border-color);">
                                            <th style="text-align: left; padding: 10px;">Profile Name</th>
                                            <th style="text-align: left; padding: 10px;">Primary Concern</th>
                                            <th style="text-align: left; padding: 10px;">Size</th>
                                            <th style="text-align: left; padding: 10px;">Last Updated</th>
                                            <th style="text-align: center; padding: 10px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="profiles-table-body">
                                        <tr>
                                            <td style="padding: 10px;">Urban Safety Advocates</td>
                                            <td style="padding: 10px;">Road Safety</td>
                                            <td style="padding: 10px;">1,250</td>
                                            <td style="padding: 10px;">Oct 15, 2023</td>
                                            <td style="padding: 10px; text-align: center;">
                                                <button class="btn-icon blue"><i class="fas fa-edit"></i></button>
                                                <button class="btn-icon red"><i class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px;">Senior Cyber Awareness</td>
                                            <td style="padding: 10px;">Cybersecurity</td>
                                            <td style="padding: 10px;">850</td>
                                            <td style="padding: 10px;">Oct 10, 2023</td>
                                            <td style="padding: 10px; text-align: center;">
                                                <button class="btn-icon blue"><i class="fas fa-edit"></i></button>
                                                <button class="btn-icon red"><i class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>
                        
                        <!-- Segmentation Tools Section -->
                        <section id="audience-section-segmentation" class="hidden">
                            <h1 class="section-title">Segmentation Tools</h1>
                            
                            <div class="card">
                                <div class="card-title">Create New Segment</div>
                                <form id="segment-form">
                                    <div class="form-group">
                                        <label class="form-label">Segment Name</label>
                                        <input type="text" class="form-input" placeholder="e.g., Parents with Young Children" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Add Criteria</label>
                                        <div style="display: flex; gap: 10px;">
                                            <select class="form-select" id="criteria-field">
                                                <option value="age">Age</option>
                                                <option value="location">Location</option>
                                                <option value="behavior">Behavior</option>
                                                <option value="interest">Interest</option>
                                            </select>
                                            <select class="form-select" id="criteria-operator">
                                                <option value="equals">Equals</option>
                                                <option value="contains">Contains</option>
                                                <option value="greater">Greater than</option>
                                                <option value="less">Less than</option>
                                            </select>
                                            <input type="text" class="form-input" id="criteria-value" placeholder="Value">
                                            <button type="button" class="btn btn-secondary" id="add-criteria">Add</button>
                                        </div>
                                    </div>
                                    
                                    <div class="criteria-list" id="criteria-list">
                                        <!-- Criteria will be added here -->
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Create Segment</button>
                                </form>
                            </div>
                            
                            <div class="card mt-20">
                                <div class="card-title">Segment Performance</div>
                                <div class="chart-wrapper">
                                    <canvas id="segment-chart"></canvas>
                                </div>
                            </div>
                        </section>
                        
                        <!-- Personal Outreach Section -->
                        <section id="audience-section-personal-outreach" class="hidden">
                            <h1 class="section-title">Personal Outreach</h1>
                            
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Create Outreach Campaign</h3>
                                </div>
                                
                                <form id="outreach-form">
                                    <div class="form-group">
                                        <label class="form-label" for="outreach-name">Campaign Name</label>
                                        <input type="text" id="outreach-name" name="name" class="form-input" placeholder="e.g., Winter Safety Tips" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label" for="outreach-segment">Select Audience Segment</label>
                                        <select id="outreach-segment" name="segment" class="form-select" required>
                                            <option value="">-- Select a segment --</option>
                                            <option value="urban">Urban Safety Advocates</option>
                                            <option value="seniors">Senior Cyber Awareness</option>
                                            <option value="community">Community Emergency Response</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label" for="outreach-template">Message Template</label>
                                        <select id="outreach-template" name="template" class="form-select">
                                            <option value="">-- Select a template --</option>
                                            <option value="safety">Safety Awareness</option>
                                            <option value="cyber">Cybersecurity Tips</option>
                                            <option value="emergery">Emergency Preparedness</option>
                                            <option value="custom">Custom Message</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label" for="outreach-message">Personalized Message</label>
                                        <div class="personalization-tags">
                                            <span class="personalization-tag" data-tag="{first_name}">First Name</span>
                                            <span class="personalization-tag" data-tag="{last_name}">Last Name</span>
                                            <span class="personalization-tag" data-tag="{location}">Location</span>
                                            <span class="personalization-tag" data-tag="{concern}">Safety Concern</span>
                                        </div>
                                        <textarea id="outreach-message" name="message" class="form-textarea" rows="5" placeholder="Compose your personalized message here..." required></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Delivery Method</label>
                                        <div style="display: flex; gap: 20px;">
                                            <label style="display: flex; align-items: center; gap: 5px;">
                                                <input type="checkbox" name="delivery" value="email"> Email
                                            </label>
                                            <label style="display: flex; align-items: center; gap: 5px;">
                                                <input type="checkbox" name="delivery" value="sms"> SMS
                                            </label>
                                            <label style="display: flex; align-items: center; gap: 5px;">
                                                <input type="checkbox" name="delivery" value="push"> Push Notification
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Schedule</label>
                                        <div style="display: flex; gap: 20px;">
                                            <label style="display: flex; align-items: center; gap: 5px;">
                                                <input type="radio" name="schedule" value="now" checked> Send immediately
                                            </label>
                                            <label style="display: flex; align-items: center; gap: 5px;">
                                                <input type="radio" name="schedule" value="later"> Schedule for later
                                            </label>
                                        </div>
                                        <div id="schedule-datetime" style="display: none; margin-top: 10px;">
                                            <input type="datetime-local" class="form-input" style="max-width: 300px;">
                                        </div>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="reset" class="btn-secondary">Clear</button>
                                        <button type="submit" class="btn-primary">Create Campaign</button>
                                    </div>
                                </form>
                            </div>
                            
                            <div class="card mt-20">
                                <div class="card-header">
                                    <h3 class="card-title">Outreach Campaigns</h3>
                                </div>
                                <div class="table-container">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Campaign Name</th>
                                                <th>Audience Segment</th>
                                                <th>Last Sent</th>
                                                <th>Open Rate</th>
                                                <th>Click Rate</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="outreach-table-body">
                                            <tr>
                                                <td>Winter Safety Tips</td>
                                                <td>Urban Safety Advocates</td>
                                                <td>2 days ago</td>
                                                <td>28.4%</td>
                                                <td>15.2%</td>
                                                <td><span class="status-badge active">Active</span></td>
                                                <td>
                                                    <button class="btn-icon blue" data-action="view" aria-label="View Campaign"><i class="fas fa-eye"></i></button>
                                                    <button class="btn-icon blue" data-action="copy" aria-label="Duplicate Campaign"><i class="fas fa-copy"></i></button>
                                                    <button class="btn-icon blue" data-action="edit" aria-label="Edit Campaign"><i class="fas fa-edit"></i></button>
                                                    <button class="btn-icon red" data-action="delete" aria-label="Delete Campaign"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Phishing Awareness</td>
                                                <td>Senior Cyber Awareness</td>
                                                <td>1 week ago</td>
                                                <td>32.7%</td>
                                                <td>18.9%</td>
                                                <td><span class="status-badge completed">Completed</span></td>
                                                <td>
                                                    <button class="btn-icon blue" data-action="view" aria-label="View Campaign"><i class="fas fa-eye"></i></button>
                                                    <button class="btn-icon blue" data-action="copy" aria-label="Duplicate Campaign"><i class="fas fa-copy"></i></button>
                                                    <button class="btn-icon blue" data-action="edit" aria-label="Edit Campaign"><i class="fas fa-edit"></i></button>
                                                    <button class="btn-icon red" data-action="delete" aria-label="Delete Campaign"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Emergency Preparedness</td>
                                                <td>Community Emergency Response</td>
                                                <td>2 weeks ago</td>
                                                <td>41.5%</td>
                                                <td>22.3%</td>
                                                <td><span class="status-badge completed">Completed</span></td>
                                                <td>
                                                    <button class="btn-icon blue" data-action="view" aria-label="View Campaign"><i class="fas fa-eye"></i></button>
                                                    <button class="btn-icon blue" data-action="copy" aria-label="Duplicate Campaign"><i class="fas fa-copy"></i></button>
                                                    <button class="btn-icon blue" data-action="edit" aria-label="Edit Campaign"><i class="fas fa-edit"></i></button>
                                                    <button class="btn-icon red" data-action="delete" aria-label="Delete Campaign"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </section>
                        
                        <!-- SMS Alerts Section -->
                        <section id="audience-section-sms-alerts">
                            <div class="page-header">
                                <h1 class="page-title">SMS Alert Notifications</h1>
                                <p class="page-description">Create and manage SMS alerts for your audience segments</p>
                            </div>
                            
                            <div class="card mb-20">
                                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                                    <h3 class="card-title">Create New SMS Alert</h3>
                                    <button class="btn-text" id="toggle-template-section" aria-expanded="false" aria-controls="quick-templates">
                                        <i class="fas fa-lightbulb"></i> Show Quick Templates
                                    </button>
                                </div>
                                
                                <div id="quick-templates" class="card-section" style="display: none; border-bottom: 1px solid var(--border-color); padding-bottom: 1.5rem;">
                                    <h4 class="section-subtitle">Quick Templates</h4>
                                    <div class="template-buttons" style="display: flex; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem;">
                                        <button type="button" class="btn-outline" data-sms-template data-title="Road Safety Reminder" data-segment="urban" data-message="Reminder: Please follow road safety rules. Wear seatbelts and obey speed limits.">
                                            <i class="fas fa-car-side"></i> Road Safety
                                        </button>
                                        <button type="button" class="btn-outline" data-sms-template data-title="Phishing Alert" data-segment="seniors" data-message="Beware of phishing texts. Never share OTPs or personal info via SMS.">
                                            <i class="fas fa-shield-alt"></i> Phishing Alert
                                        </button>
                                        <button type="button" class="btn-outline" data-sms-template data-title="Emergency Drill" data-segment="community" data-message="Community emergency drill this weekend. Please be prepared and participate.">
                                            <i class="fas fa-bullhorn"></i> Emergency Drill
                                        </button>
                                    </div>
                                </div>
                                
                                <form id="sms-alert-form" class="card-section">
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label class="form-label" for="sms-title">Alert Title <span class="required">*</span></label>
                                            <input type="text" id="sms-title" name="title" class="form-input" placeholder="e.g., Road Safety Reminder" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="sms-segment">Audience Segment <span class="required">*</span></label>
                                            <select id="sms-segment" name="segment" class="form-select" required>
                                                <option value="">-- Select a segment --</option>
                                                <option value="urban">Urban Residents</option>
                                                <option value="seniors">Seniors</option>
                                                <option value="community">Community Emergency Response</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                            <label class="form-label" for="sms-message">Message <span class="required">*</span></label>
                                            <span class="text-sm text-muted" id="char-count">0/160 characters</span>
                                        </div>
                                        <textarea id="sms-message" name="message" class="form-textarea" maxlength="160" rows="3" 
                                                  placeholder="Compose your SMS message..." required></textarea>
                                    </div>
                                    
                                    <div class="form-grid mb-20">
                                        <div class="form-group">
                                            <label class="form-label" for="sms-schedule-date">Send Date <span class="required">*</span></label>
                                            <input type="date" id="sms-schedule-date" name="date" class="form-input" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="sms-schedule-time">Send Time <span class="required">*</span></label>
                                            <input type="time" id="sms-schedule-time" name="time" class="form-input" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-actions" style="border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
                                        <button type="reset" class="btn-text">
                                            <i class="fas fa-undo-alt"></i> Clear Form
                                        </button>
                                        <div class="button-group">
                                            <button type="button" class="btn-secondary" id="save-draft">
                                                <i class="far fa-save"></i> Save Draft
                                            </button>
                                            <button type="submit" class="btn-primary">
                                                <i class="fas fa-paper-plane"></i> Schedule SMS
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            
                            <div class="card">
                                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                                    <h3 class="card-title">Scheduled SMS Alerts</h3>
                                    <div class="filter-controls" style="display: flex; gap: 0.75rem;">
                                        <div class="form-group" style="margin: 0;">
                                            <label for="status-filter" class="sr-only">Filter by status</label>
                                            <select id="status-filter" class="form-select form-select-sm">
                                                <option value="">All Statuses</option>
                                                <option value="active">Active</option>
                                                <option value="scheduled">Scheduled</option>
                                                <option value="draft">Draft</option>
                                            </select>
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label for="segment-filter" class="sr-only">Filter by segment</label>
                                            <select id="segment-filter" class="form-select form-select-sm">
                                                <option value="">All Segments</option>
                                                <option value="urban">Urban Residents</option>
                                                <option value="seniors">Seniors</option>
                                                <option value="community">Community</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 25%;">Alert</th>
                                                <th style="width: 20%;">Segment</th>
                                                <th style="width: 15%;">Scheduled For</th>
                                                <th style="width: 15%;">Status</th>
                                                <th style="width: 25%;" class="text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="sms-table-body">
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="icon-wrapper bg-blue-50 text-blue-600 mr-3">
                                                            <i class="fas fa-car-side"></i>
                                                        </div>
                                                        <div>
                                                            <div class="font-weight-600">Road Safety Reminder</div>
                                                            <div class="text-muted text-sm">Created: Oct 12, 2023</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>Urban Residents</td>
                                                <td>Oct 14, 2023<br><span class="text-muted text-sm">10:00 AM</span></td>
                                                <td><span class="status-badge active">Active</span></td>
                                                <td class="text-right">
                                                    <div class="btn-group">
                                                        <button class="btn-icon" data-action="view" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn-icon" data-action="edit" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn-icon text-danger" data-action="delete" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="icon-wrapper bg-purple-50 text-purple-600 mr-3">
                                                            <i class="fas fa-shield-alt"></i>
                                                        </div>
                                                        <div>
                                                            <div class="font-weight-600">Cybersecurity Awareness</div>
                                                            <div class="text-muted text-sm">Created: Oct 15, 2023</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>Seniors</td>
                                                <td>Oct 21, 2023<br><span class="text-muted text-sm">2:30 PM</span></td>
                                                <td><span class="status-badge planning">Scheduled</span></td>
                                                <td class="text-right">
                                                    <div class="btn-group">
                                                        <button class="btn-icon" data-action="view" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn-icon" data-action="edit" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn-icon text-danger" data-action="delete" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    
                                    <div id="sms-empty-state" class="empty-state" style="display: none;">
                                        <div class="empty-state-icon">
                                            <i class="fas fa-comment-alt"></i>
                                        </div>
                                        <h4>No SMS Alerts Found</h4>
                                        <p>You haven't created any SMS alerts yet. Get started by creating a new alert.</p>
                                        <button class="btn-primary" id="create-first-alert">
                                            <i class="fas fa-plus"></i> Create Alert
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="card-footer" style="border-top: 1px solid var(--border-color); padding-top: 1rem;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted text-sm">
                                            Showing <span id="showing-count">2</span> of <span id="total-count">2</span> alerts
                                        </div>
                                        <div class="pagination">
                                            <button class="btn-icon" disabled>
                                                <i class="fas fa-chevron-left"></i>
                                            </button>
                                            <span class="px-3">1</span>
                                            <button class="btn-icon" disabled>
                                                <i class="fas fa-chevron-right"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                        
                        <!-- Audience Lists Section -->
                        <section id="audience-section-lists" class="hidden">
                            <h1 class="section-title">Audience Lists</h1>
                            
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Manage Audience Lists</h3>
                                    <button class="btn-primary">Create New List</button>
                                </div>
                                <div class="search-container">
                                    <input type="text" class="search-input" placeholder="Search lists..." style="width: 300px;">
                                </div>
                            </div>
                            
                            <div class="content-grid mt-20">
                                <div class="audience-card">
                                    <div class="audience-card-header">
                                        <h4>Urban Safety Advocates</h4>
                                        <span class="status-badge active">Active</span>
                                    </div>
                                    <p>Residents interested in urban safety initiatives</p>
                                    <div class="audience-stats">
                                        <div class="audience-stat">
                                            <div class="audience-stat-value">1,245</div>
                                            <div class="audience-stat-label">Contacts</div>
                                        </div>
                                        <div class="audience-stat">
                                            <div class="audience-stat-value">24.7%</div>
                                            <div class="audience-stat-label">Engagement</div>
                                        </div>
                                    </div>
                                    <div class="audience-card-actions">
                                        <button class="btn-secondary">View</button>
                                        <button class="btn-primary">Export</button>
                                    </div>
                                </div>
                                
                                <div class="audience-card">
                                    <div class="audience-card-header">
                                        <h4>Senior Cyber Awareness</h4>
                                        <span class="status-badge active">Active</span>
                                    </div>
                                    <p>Seniors interested in cybersecurity education</p>
                                    <div class="audience-stats">
                                        <div class="audience-stat">
                                            <div class="audience-stat-value">874</div>
                                            <div class="audience-stat-label">Contacts</div>
                                        </div>
                                        <div class="audience-stat">
                                            <div class="audience-stat-value">31.2%</div>
                                            <div class="audience-stat-label">Engagement</div>
                                        </div>
                                    </div>
                                    <div class="audience-card-actions">
                                        <button class="btn-secondary">View</button>
                                        <button class="btn-primary">Export</button>
                                    </div>
                                </div>
                                
                                <div class="audience-card">
                                    <div class="audience-card-header">
                                        <h4>Community Emergency Response</h4>
                                        <span class="status-badge active">Active</span>
                                    </div>
                                    <p>Community members trained in emergency response</p>
                                    <div class="audience-stats">
                                        <div class="audience-stat">
                                            <div class="audience-stat-value">1,532</div>
                                            <div class="audience-stat-label">Contacts</div>
                                        </div>
                                        <div class="audience-stat">
                                            <div class="audience-stat-value">42.5%</div>
                                            <div class="audience-stat-label">Engagement</div>
                                        </div>
                                    </div>
                                    <div class="audience-card-actions">
                                        <button class="btn-secondary">View</button>
                                        <button class="btn-primary">Export</button>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </main>
                </div>
            </div>

            <!-- Event Management Module -->
            <div id="events" class="module-section">
                <div class="page-header">
                    <h2 class="page-title">Event Management</h2>
                    <p class="page-description">Plan and manage events for your public safety campaigns</p>
                </div>
                
                <div class="content-dashboard-layout">
                    <!-- Sidebar for Event Module -->
                    <aside class="content-sidebar">
                        <div class="nav-item active" data-event-section="upcoming">Upcoming Events</div>
                        <div class="nav-item" data-event-section="create">Create Event</div>
                        <div class="nav-item" data-event-section="analytics">Analytics</div>
                        
                        <div class="category-list">
                            <h3>Event Types</h3>
                            <div class="category-item active" data-event-category="all">All Events</div>
                            <div class="category-item" data-event-category="workshops">Workshops</div>
                            <div class="category-item" data-event-category="seminars">Seminars</div>
                            <div class="category-item" data-event-category="training">Training</div>
                        </div>
                        
                        <div class="stats">
                            <h3>Event Stats</h3>
                            <div class="stat-item">
                                <span>Total Events</span>
                                <span>15</span>
                            </div>
                            <div class="stat-item">
                                <span>This Month</span>
                                <span>3</span>
                            </div>
                            <div class="stat-item">
                                <span>Avg. Attendance</span>
                                <span>35</span>
                            </div>
                        </div>
                        
                        <div class="mt-20">
                            <h3>Quick Actions</h3>
                            <button class="btn-primary" id="quickNewEventBtn">
                                <i class="fas fa-plus"></i>
                                <span>New Event</span>
                            </button>
                            <button class="btn-secondary">
                                <i class="fas fa-calendar-alt"></i>
                                <span>View Calendar</span>
                            </button>
                        </div>
                    </aside>
                    
                    <!-- Main Content for Event Module -->
                    <main class="content-main">
                        <!-- Upcoming Events Section -->
                        <section id="event-section-upcoming">
                            <h1 class="section-title">Upcoming Events</h1>
                            <div class="search-container">
                                <input type="text" class="search-input" placeholder="Search events...">
                                <select class="filter-select">
                                    <option value="all">All Types</option>
                                    <option value="workshops">Workshops</option>
                                    <option value="seminars">Seminars</option>
                                    <option value="training">Training</option>
                                </select>
                                <button class="btn btn-primary">Filter</button>
                                <button class="btn-primary" id="newEventBtn">
                                    <i class="fas fa-plus"></i>
                                    <span>New Event</span>
                                </button>
                            </div>
                            
                            <div class="events-list">
                                <div class="event-card">
                                    <div class="event-header">
                                        <h3 class="event-title">Road Safety Workshop</h3>
                                        <div class="event-date">
                                            <i class="fas fa-calendar"></i>
                                            <span>October 15, 2023 • 10:00 AM</span>
                                        </div>
                                    </div>
                                    <div class="event-details">
                                        <div class="event-detail">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span>City Community Center</span>
                                        </div>
                                        <div class="event-detail">
                                            <i class="fas fa-users"></i>
                                            <span>45 registered attendees</span>
                                        </div>
                                        <div class="event-detail">
                                            <i class="fas fa-tag"></i>
                                            <span>Road Safety Campaign</span>
                                        </div>
                                    </div>
                                    <div class="event-actions">
                                        <button class="btn-primary">Manage Attendees</button>
                                        <button class="btn-secondary">Send Reminders</button>
                                        <button class="btn-icon blue"><i class="fas fa-edit"></i></button>
                                    </div>
                                </div>

                                <div class="event-card">
                                    <div class="event-header">
                                        <h3 class="event-title">Cyber Safety Seminar</h3>
                                        <div class="event-date">
                                            <i class="fas fa-calendar"></i>
                                            <span>October 22, 2023 • 2:00 PM</span>
                                        </div>
                                    </div>
                                    <div class="event-details">
                                        <div class="event-detail">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span>Central Library</span>
                                        </div>
                                        <div class="event-detail">
                                            <i class="fas fa-users"></i>
                                            <span>32 registered attendees</span>
                                        </div>
                                        <div class="event-detail">
                                            <i class="fas fa-tag"></i>
                                            <span>Cyber Security Campaign</span>
                                        </div>
                                    </div>
                                    <div class="event-actions">
                                        <button class="btn-primary">Manage Attendees</button>
                                        <button class="btn-secondary">Send Reminders</button>
                                        <button class="btn-icon blue"><i class="fas fa-edit"></i></button>
                                    </div>
                                </div>

                                <div class="event-card">
                                    <div class="event-header">
                                        <h3 class="event-title">Emergency Response Training</h3>
                                        <div class="event-date">
                                            <i class="fas fa-calendar"></i>
                                            <span>November 5, 2023 • 9:00 AM</span>
                                        </div>
                                    </div>
                                    <div class="event-details">
                                        <div class="event-detail">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span>Fire Station #3</span>
                                        </div>
                                        <div class="event-detail">
                                            <i class="fas fa-users"></i>
                                            <span>28 registered attendees</span>
                                        </div>
                                        <div class="event-detail">
                                            <i class="fas fa-tag"></i>
                                            <span>Disaster Preparedness</span>
                                        </div>
                                    </div>
                                    <div class="event-actions">
                                        <button class="btn-primary">Manage Attendees</button>
                                        <button class="btn-secondary">Send Reminders</button>
                                        <button class="btn-icon blue"><i class="fas fa-edit"></i></button>
                                    </div>
                                </div>
                            </div>
                        </section>
                        
                        <!-- Create Event Section -->
                        <section id="event-section-create" class="hidden">
                            <h1 class="section-title">Create New Event</h1>
                            
                            <div class="card">
                                <h3 class="card-title">Event Details</h3>
                                <form class="event-form" id="eventForm">
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label class="form-label">Event Name</label>
                                            <input type="text" class="form-input" id="eventName" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Campaign</label>
                                            <select class="form-input" id="eventCampaign" required>
                                                <option value="">Select Campaign</option>
                                                <option value="road-safety">Road Safety Campaign</option>
                                                <option value="cyber-security">Cyber Security Campaign</option>
                                                <option value="fire-prevention">Fire Prevention Campaign</option>
                                                <option value="disaster-preparedness">Disaster Preparedness</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Date</label>
                                            <input type="date" class="form-input" id="eventDate" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Time</label>
                                            <input type="time" class="form-input" id="eventTime" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Location</label>
                                        <input type="text" class="form-input" id="eventLocation" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-input" id="eventDescription" rows="4" required></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Maximum Attendees</label>
                                        <input type="number" class="form-input" id="eventMaxAttendees" min="1">
                                    </div>
                                    <div class="form-actions">
                                        <button type="button" class="btn-secondary" id="cancelEventBtn">Cancel</button>
                                        <button type="submit" class="btn-primary">Create Event</button>
                                    </div>
                                </form>
                            </div>
                        </section>
                        
                        <!-- Analytics Section -->
                        <section id="event-section-analytics" class="hidden">
                            <h1 class="section-title">Event Analytics</h1>
                            
                            <div class="card">
                                <h3 class="card-title">Event Attendance</h3>
                                <div class="chart-wrapper">
                                    <canvas id="eventAttendanceChart"></canvas>
                                </div>
                            </div>
                        </section>
                    </main>
                </div>
            </div>

            <!-- Feedback & Surveys Module -->
            <div id="feedback" class="module-section">
                <div class="page-header">
                    <h2 class="page-title">Feedback & Surveys</h2>
                    <p class="page-description">Collect and analyze feedback from your audience</p>
                </div>
                
                <div class="content-dashboard-layout">
                    <!-- Sidebar for Feedback Module -->
                    <aside class="content-sidebar">
                        <div class="nav-item active" data-feedback-section="surveys">Active Surveys</div>
                        <div class="nav-item" data-feedback-section="create">Create Survey</div>
                        <div class="nav-item" data-feedback-section="responses">Responses</div>
                        <div class="nav-item" data-feedback-section="analytics">Analytics</div>
                        
                        <div class="category-list">
                            <h3>Survey Types</h3>
                            <div class="category-item active" data-survey-category="all">All Surveys</div>
                            <div class="category-item" data-survey-category="feedback">Feedback</div>
                            <div class="category-item" data-survey-category="assessment">Assessment</div>
                            <div class="category-item" data-survey-category="poll">Quick Poll</div>
                        </div>
                        
                        <div class="stats">
                            <h3>Survey Stats</h3>
                            <div class="stat-item">
                                <span>Active Surveys</span>
                                <span>4</span>
                            </div>
                            <div class="stat-item">
                                <span>Total Responses</span>
                                <span>1,243</span>
                            </div>
                            <div class="stat-item">
                                <span>Avg. Rating</span>
                                <span>4.2/5</span>
                            </div>
                        </div>
                        
                        <div class="mt-20">
                            <h3>Quick Actions</h3>
                            <button class="btn-primary" id="quickNewSurveyBtn">
                                <i class="fas fa-plus"></i>
                                <span>New Survey</span>
                            </button>
                            <button class="btn-secondary">
                                <i class="fas fa-chart-bar"></i>
                                <span>View Reports</span>
                            </button>
                        </div>
                    </aside>
                    
                    <!-- Main Content for Feedback Module -->
                    <main class="content-main">
                        <!-- Active Surveys Section -->
                        <section id="feedback-section-surveys">
                            <h1 class="section-title">Active Surveys</h1>
                            <div class="search-container">
                                <input type="text" class="search-input" placeholder="Search surveys...">
                                <select class="filter-select">
                                    <option value="all">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="draft">Draft</option>
                                    <option value="completed">Completed</option>
                                </select>
                                <button class="btn btn-primary">Filter</button>
                                <button class="btn-primary" id="newSurveyBtn">
                                    <i class="fas fa-plus"></i>
                                    <span>New Survey</span>
                                </button>
                            </div>

                            
                            <div class="survey-list">
                                <div class="survey-item">
                                    <div class="survey-header">
                                        <h3 class="survey-title">Road Safety Awareness Survey</h3>
                                        <span class="status-badge active">Active</span>
                                    </div>
                                    <div class="survey-details">
                                        <div class="event-detail">
                                            <i class="fas fa-chart-bar"></i>
                                            <span>245 responses</span>
                                        </div>
                                        <div class="event-detail">
                                            <i class="fas fa-calendar"></i>
                                            <span>Ends: October 30, 2023</span>
                                        </div>
                                        <div class="event-detail">
                                            <i class="fas fa-tag"></i>
                                            <span>Road Safety Campaign</span>
                                        </div>
                                    </div>
                                    <div class="survey-actions">
                                        <button class="btn-primary">View Results</button>
                                        <button class="btn-secondary">Share Survey</button>
                                        <button class="btn-icon blue"><i class="fas fa-edit"></i></button>
                                    </div>
                                </div>

                                <div class="survey-item">
                                    <div class="survey-header">
                                        <h3 class="survey-title">Cybersecurity Knowledge Assessment</h3>
                                        <span class="status-badge planning">Draft</span>
                                    </div>
                                    <div class="survey-details">
                                        <div class="event-detail">
                                            <i class="fas fa-chart-bar"></i>
                                            <span>0 responses</span>
                                        </div>
                                        <div class="event-detail">
                                            <i class="fas fa-calendar"></i>
                                            <span>Not published</span>
                                        </div>
                                        <div class="event-detail">
                                            <i class="fas fa-tag"></i>
                                            <span>Cyber Security Campaign</span>
                                        </div>
                                    </div>
                                    <div class="survey-actions">
                                        <button class="btn-primary">Preview</button>
                                        <button class="btn-secondary">Publish</button>
                                        <button class="btn-icon blue"><i class="fas fa-edit"></i></button>
                                    </div>
                                </div>
                                
                                <div class="survey-item">
                                    <div class="survey-header">
                                        <h3 class="survey-title">Emergency Preparedness Survey</h3>
                                        <span class="status-badge completed">Completed</span>
                                    </div>
                                    <div class="survey-details">
                                        <div class="event-detail">
                                            <i class="fas fa-chart-bar"></i>
                                            <span>189 responses</span>
                                        </div>
                                        <div class="event-detail">
                                            <i class="fas fa-calendar"></i>
                                            <span>Ended: October 15, 2023</span>
                                        </div>
                                        <div class="event-detail">
                                            <i class="fas fa-tag"></i>
                                            <span>Disaster Preparedness</span>
                                        </div>
                                    </div>
                                    <div class="survey-actions">
                                        <button class="btn-primary">View Results</button>
                                        <button class="btn-secondary">Download Report</button>
                                        <button class="btn-icon blue"><i class="fas fa-copy"></i></button>
                                    </div>
                                </div>
                            </div>
                        </section>
                        
                        <!-- Create Survey Section -->
                        <section id="feedback-section-create" class="hidden">
                            <h1 class="section-title">Create New Survey</h1>
                            
                            <div class="card">
                                <h3 class="card-title">Survey Details</h3>
                                <form class="survey-form" id="surveyForm">
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label class="form-label">Survey Title</label>
                                            <input type="text" class="form-input" id="surveyTitle" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Campaign</label>
                                            <select class="form-input" id="surveyCampaign" required>
                                                <option value="">Select Campaign</option>
                                                <option value="road-safety">Road Safety Campaign</option>
                                                <option value="cyber-security">Cyber Security Campaign</option>
                                                <option value="fire-prevention">Fire Prevention Campaign</option>
                                                <option value="disaster-preparedness">Disaster Preparedness</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Start Date</label>
                                            <input type="date" class="form-input" id="surveyStartDate" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">End Date</label>
                                            <input type="date" class="form-input" id="surveyEndDate" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-input" id="surveyDescription" rows="4" required></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Target Audience</label>
                                        <select class="form-input" id="surveyAudience" multiple>
                                            <option value="general">General Public</option>
                                            <option value="seniors">Senior Citizens</option>
                                            <option value="parents">Parents with Children</option>
                                            <option value="students">School Students</option>
                                            <option value="drivers">Drivers</option>
                                            <option value="businesses">Local Businesses</option>
                                        </select>
                                    </div>
                                    <div class="form-actions">
                                        <button type="button" class="btn-secondary" id="cancelSurveyBtn">Cancel</button>
                                        <button type="submit" class="btn-primary">Create Survey</button>
                                    </div>
                                </form>
                            </div>
                        </section>
                        
                        <!-- Responses Section -->
                        <section id="feedback-section-responses" class="hidden">
                            <h1 class="section-title">Survey Responses</h1>
                            
                            <div class="card">
                                <h3 class="card-title">Response Analytics</h3>
                                <div class="chart-wrapper">
                                    <canvas id="surveyResponseChart"></canvas>
                                </div>
                            </div>
                        </section>
                        
                        <!-- Analytics Section -->
                        <section id="feedback-section-analytics" class="hidden">
                            <h1 class="section-title">Survey Analytics</h1>
                            
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <div class="stat-info">
                                            <p class="stat-label">Response Rate</p>
                                            <h3 class="stat-value">68%</h3>
                                        </div>
                                        <div class="stat-icon blue">
                                            <i class="fas fa-percentage"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <div class="stat-info">
                                            <p class="stat-label">Avg. Completion Time</p>
                                            <h3 class="stat-value">3:42</h3>
                                        </div>
                                        <div class="stat-icon green">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <div class="stat-info">
                                            <p class="stat-label">Satisfaction Score</p>
                                            <h3 class="stat-value">4.2/5</h3>
                                        </div>
                                        <div class="stat-icon purple">
                                            <i class="fas fa-star"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card">
                                <h3 class="card-title">Detailed Analytics</h3>
                                <div class="chart-wrapper">
                                    <canvas id="detailedSurveyChart"></canvas>
                                </div>
                            </div>
                        </section>
                    </main>
                </div>
            </div>

            <!-- Impact Monitoring Module -->
            <div id="monitoring" class="module-section">
                <div class="page-header">
                    <h2 class="page-title">Impact Monitoring</h2>
                    <p class="page-description">Track and measure the impact of your public safety campaigns</p>
                </div>

                <div class="impact-kpi">
                    <div class="kpi-card">
                        <div class="kpi-label">Awareness Level</div>
                        <div class="kpi-value">64%</div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 64%"></div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">Behavior Change</div>
                        <div class="kpi-value">42%</div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 42%"></div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">Satisfaction Rate</div>
                        <div class="kpi-value">78%</div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 78%"></div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-label">Recommendation Score</div>
                        <div class="kpi-value">8.2/10</div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 82%"></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h3 class="card-title">Impact Over Time</h3>
                    <div class="chart-wrapper">
                        <canvas id="impactOverTimeChart"></canvas>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Campaign Impact Reports</h3>
                        <button class="btn-primary" id="newReportBtn">
                            <i class="fas fa-plus"></i>
                            <span>Generate Report</span>
                        </button>
                    </div>

                    <div class="report-grid">
                        <div class="report-card">
                            <div class="report-header">
                                <h4 class="report-title">Road Safety Campaign Report</h4>
                                <span class="report-date">Oct 15, 2023</span>
                            </div>
                            <p>Analysis of road safety awareness campaign effectiveness</p>
                            <div class="report-actions">
                                <button class="btn-secondary">View</button>
                                <button class="btn-primary">Download</button>
                            </div>
                        </div>

                        <div class="report-card">
                            <div class="report-header">
                                <h4 class="report-title">Cybersecurity Campaign Report</h4>
                                <span class="report-date">Sep 30, 2023</span>
                            </div>
                            <p>Evaluation of cybersecurity awareness initiatives</p>
                            <div class="report-actions">
                                <button class="btn-secondary">View</button>
                                <button class="btn-primary">Download</button>
                            </div>
                        </div>

                        <div class="report-card">
                            <div class="report-header">
                                <h4 class="report-title">Quarterly Impact Summary</h4>
                                <span class="report-date">Sep 30, 2023</span>
                            </div>
                            <p>Summary of all campaign impacts for Q3 2023</p>
                            <div class="report-actions">
                                <button class="btn-secondary">View</button>
                                <button class="btn-primary">Download</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h3 class="card-title">Set Impact Goals</h3>
                    <form class="goal-form" id="goalForm">
                        <div class="goal-item">
                            <div class="goal-header">
                                <label class="form-label">Awareness Goal</label>
                                <span>Current: 64%</span>
                            </div>
                            <input type="range" class="form-input" min="0" max="100" value="75" id="awarenessGoal">
                            <div class="goal-header">
                                <span>0%</span>
                                <span id="awarenessGoalValue">75%</span>
                                <span>100%</span>
                            </div>
                        </div>

                        <div class="goal-item">
                            <div class="goal-header">
                                <label class="form-label">Behavior Change Goal</label>
                                <span>Current: 42%</span>
                            </div>
                            <input type="range" class="form-input" min="0" max="100" value="60" id="behaviorGoal">
                            <div class="goal-header">
                                <span>0%</span>
                                <span id="behaviorGoalValue">60%</span>
                                <span>100%</span>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Save Goals</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Collaboration Module -->
            <div id="collaboration" class="module-section">
                <div class="page-header">
                    <h2 class="page-title">Collaboration Hub</h2>
                    <p class="page-description">Work together with your team on public safety campaigns</p>
                </div>

                <div class="content-dashboard-layout">
                    <!-- Sidebar for Collaboration Module -->
                    <aside class="content-sidebar">
                        <div class="nav-item active" data-collab-section="team">Team Members</div>
                        <div class="nav-item" data-collab-section="projects">Projects</div>
                        <div class="nav-item" data-collab-section="messages">Messages</div>
                        <div class="nav-item" data-collab-section="tasks">Tasks</div>
                        
                        <div class="stats">
                            <h3>Team Stats</h3>
                            <div class="stat-item">
                                <span>Active Members</span>
                                <span>8</span>
                            </div>
                            <div class="stat-item">
                                <span>Active Projects</span>
                                <span>5</span>
                            </div>
                            <div class="stat-item">
                                <span>Completed Tasks</span>
                                <span>42</span>
                            </div>
                        </div>
                        
                        <div class="mt-20">
                            <h3>Recent Activity</h3>
                            <div class="version-list">
                                <div>John updated Road Safety Campaign</div>
                                <div>Sarah uploaded new content</div>
                                <div>Mike completed audience analysis</div>
                            </div>
                        </div>
                    </aside>
                    
                    <!-- Main Content for Collaboration Module -->
                    <main class="content-main">
                        <!-- Team Members Section -->
                        <section id="collab-section-team">
                            <h1 class="section-title">Team Members</h1>
                            
                            <div class="search-container">
                                <input type="text" class="search-input" placeholder="Search team members...">
                                <button class="btn-primary">
                                    <i class="fas fa-plus"></i>
                                    <span>Add Member</span>
                                </button>
                            </div>
                            
                            <div class="team-list">
                                <div class="team-member">
                                    <div class="user-avatar">J</div>
                                    <div class="member-info">
                                        <h4>John Doe</h4>
                                        <p class="member-role">Campaign Manager</p>
                                    </div>
                                    <div class="member-actions">
                                        <button class="btn-icon blue"><i class="fas fa-envelope"></i></button>
                                        <button class="btn-icon blue"><i class="fas fa-edit"></i></button>
                                    </div>
                                </div>
                                
                                <div class="team-member">
                                    <div class="user-avatar">S</div>
                                    <div class="member-info">
                                        <h4>Sarah Johnson</h4>
                                        <p class="member-role">Content Creator</p>
                                    </div>
                                    <div class="member-actions">
                                        <button class="btn-icon blue"><i class="fas fa-envelope"></i></button>
                                        <button class="btn-icon blue"><i class="fas fa-edit"></i></button>
                                    </div>
                                </div>
                                
                                <div class="team-member">
                                    <div class="user-avatar">M</div>
                                    <div class="member-info">
                                        <h4>Mike Williams</h4>
                                        <p class="member-role">Data Analyst</p>
                                    </div>
                                    <div class="member-actions">
                                        <button class="btn-icon blue"><i class="fas fa-envelope"></i></button>
                                        <button class="btn-icon blue"><i class="fas fa-edit"></i></button>
                                    </div>
                                </div>
                                
                                <div class="team-member">
                                    <div class="user-avatar">E</div>
                                    <div class="member-info">
                                        <h4>Emma Brown</h4>
                                        <p class="member-role">Community Outreach</p>
                                    </div>
                                    <div class="member-actions">
                                        <button class="btn-icon blue"><i class="fas fa-envelope"></i></button>
                                        <button class="btn-icon blue"><i class="fas fa-edit"></i></button>
                                    </div>
                                </div>
                            </div>
                        </section>
                        
                        <!-- Projects Section -->
                        <section id="collab-section-projects" class="hidden">
                            <h1 class="section-title">Projects</h1>
                            
                            <div class="search-container">
                                <input type="text" class="search-input" placeholder="Search projects...">
                                <button class="btn-primary">
                                    <i class="fas fa-plus"></i>
                                    <span>New Project</span>
                                </button>
                            </div>
                            
                            <div class="project-list">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Road Safety Campaign</h3>
                                        <span class="status-badge active">Active</span>
                                    </div>
                                    <p>Develop and implement road safety awareness campaign</p>
                                    <div class="event-detail">
                                        <i class="fas fa-users"></i>
                                        <span>5 team members</span>
                                    </div>
                                    <div class="event-detail">
                                        <i class="fas fa-tasks"></i>
                                        <span>12 tasks completed of 18</span>
                                    </div>
                                    <div class="event-actions">
                                        <button class="btn-primary">View Project</button>
                                        <button class="btn-icon blue"><i class="fas fa-edit"></i></button>
                                    </div>
                                </div>
                                
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Cybersecurity Initiative</h3>
                                        <span class="status-badge planning">Planning</span>
                                    </div>
                                    <p>Create cybersecurity awareness program for seniors</p>
                                    <div class="event-detail">
                                        <i class="fas fa-users"></i>
                                        <span>3 team members</span>
                                    </div>
                                    <div class="event-detail">
                                        <i class="fas fa-tasks"></i>
                                        <span>4 tasks completed of 15</span>
                                    </div>
                                    <div class="event-actions">
                                        <button class="btn-primary">View Project</button>
                                        <button class="btn-icon blue"><i class="fas fa-edit"></i></button>
                                    </div>
                                </div>
                            </div>
                        </section>
                        
                        <!-- Messages Section -->
                        <section id="collab-section-messages" class="hidden">
                            <h1 class="section-title">Team Messages</h1>
                            
                            <div class="message-list">
                                <div class="message-item">
                                    <div class="message-avatar">J</div>
                                    <div class="message-content">
                                        <h4>John Doe</h4>
                                        <p>Has everyone reviewed the latest campaign metrics? We need to prepare for the quarterly review meeting.</p>
                                        <p class="message-time">Today, 10:30 AM</p>
                                    </div>
                                    <div class="message-actions">
                                        <button class="btn-icon blue"><i class="fas fa-reply"></i></button>
                                    </div>
                                </div>
                                
                                <div class="message-item">
                                    <div class="message-avatar">S</div>
                                    <div class="message-content">
                                        <h4>Sarah Johnson</h4>
                                        <p>I've uploaded the new safety posters to the content repository. Please review when you have a moment.</p>
                                        <p class="message-time">Yesterday, 3:45 PM</p>
                                    </div>
                                    <div class="message-actions">
                                        <button class="btn-icon blue"><i class="fas fa-reply"></i></button>
                                    </div>
                                </div>
                                
                                <div class="message-item">
                                    <div class="message-avatar">M</div>
                                    <div class="message-content">
                                        <h4>Mike Williams</h4>
                                        <p>The audience segmentation analysis is complete. I've shared the report in the documents section.</p>
                                        <p class="message-time">Yesterday, 11:20 AM</p>
                                    </div>
                                    <div class="message-actions">
                                        <button class="btn-icon blue"><i class="fas fa-reply"></i></button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="compose-area">
                                <h3>New Message</h3>
                                <form id="message-form">
                                    <div class="form-group">
                                        <label class="form-label">To</label>
                                        <select class="form-input" multiple>
                                            <option value="john">John Doe</option>
                                            <option value="sarah">Sarah Johnson</option>
                                            <option value="mike">Mike Williams</option>
                                            <option value="emma">Emma Brown</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Message</label>
                                        <textarea class="form-input" rows="4" placeholder="Type your message here..."></textarea>
                                    </div>
                                    <button type="submit" class="btn-primary">Send Message</button>
                                </form>
                            </div>
                        </section>
                        
                        <!-- Tasks Section -->
                        <section id="collab-section-tasks" class="hidden">
                            <h1 class="section-title">Team Tasks</h1>
                            
                            <div class="search-container">
                                <input type="text" class="search-input" placeholder="Search tasks...">
                                <button class="btn-primary">
                                    <i class="fas fa-plus"></i>
                                    <span>New Task</span>
                                </button>
                            </div>
                            
                            <div class="task-list">
                                <div class="task-item">
                                    <input type="checkbox" id="task1">
                                    <div class="task-info">
                                        <h4>Review campaign metrics</h4>
                                        <p class="task-due">Due: Today</p>
                                    </div>
                                    <div class="task-actions">
                                        <button class="btn-icon blue"><i class="fas fa-edit"></i></button>
                                    </div>
                                </div>
                                
                                <div class="task-item">
                                    <input type="checkbox" id="task2" checked>
                                    <div class="task-info">
                                        <h4>Upload safety posters</h4>
                                        <p class="task-due">Due: Oct 20, 2023</p>
                                    </div>
                                    <div class="task-actions">
                                        <button class="btn-icon blue"><i class="fas fa-edit"></i></button>
                                    </div>
                                </div>
                                
                                <div class="task-item">
                                    <input type="checkbox" id="task3">
                                    <div class="task-info">
                                        <h4>Prepare quarterly review</h4>
                                        <p class="task-due">Due: Oct 25, 2023</p>
                                    </div>
                                    <div class="task-actions">
                                        <button class="btn-icon blue"><i class="fas fa-edit"></i></button>
                                    </div>
                                </div>
                                
                                <div class="task-item">
                                    <input type="checkbox" id="task4">
                                    <div class="task-info">
                                        <h4>Schedule safety workshop</h4>
                                        <p class="task-due">Due: Oct 28, 2023</p>
                                    </div>
                                    <div class="task-actions">
                                        <button class="btn-icon blue"><i class="fas fa-edit"></i></button>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </main>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Chatbot Modal -->
    <div class="chat-modal" id="chatModal">
        <div class="chat-header">
            <h3>AI Campaign Assistant</h3>
            <button class="modal-close" id="closeChat">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="chat-body" id="chatBody">
            <div class="chat-message bot">
                Hello! I'm your AI Campaign Assistant. How can I help you with your public safety campaigns today?
            </div>
        </div>
        <div class="chat-footer">
            <input type="text" class="chat-input" placeholder="Type your message here..." id="chatInput">
            <button class="chat-send" id="sendChat">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>

    <script>
        // PHP data for JavaScript
        const userData = {
            id: <?php echo json_encode($user_id); ?>,
            name: <?php echo json_encode($user_name); ?>,
            email: <?php echo json_encode($user_email); ?>,
            role: <?php echo json_encode($user_role); ?>,
            avatar: <?php echo json_encode($user_avatar); ?>
        };
        
        const dashboardStats = <?php echo json_encode($stats); ?>;
        const recentCampaigns = <?php echo json_encode($recent_campaigns); ?>;
        
        // Set user data in global scope for existing JavaScript
        window.userData = userData;
        window.dashboardStats = dashboardStats;
        window.recentCampaigns = recentCampaigns;
    </script>
    
    <!-- Synchronize PHP session data with localStorage for JavaScript authentication -->
    <script>
        <?php if (isset($_SESSION['js_user_data'])): ?>
        // Parse user data from PHP session
        const phpUserData = <?php echo $_SESSION['js_user_data']; ?>;
        
        // Set localStorage items that JavaScript expects
        localStorage.setItem('user_data', JSON.stringify(phpUserData));
        localStorage.setItem('isLoggedIn', 'true');
        
        // Create a simple token for demo purposes
        const tokenData = {
            user_id: phpUserData.id,
            expires: Date.now() + (24 * 60 * 60 * 1000) // 24 hours
        };
        localStorage.setItem('auth_token', btoa(JSON.stringify(tokenData)));
        <?php endif; ?>
    </script>
    
    <script src="backend-integration.js"></script>
    <script src="index.js"></script>
</body>



</html> 