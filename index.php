<?php
/**
 * Public Safety Campaign Management System
 * Main entry point
 */

// Redirect to login page by default
header("Location: login.php");
exit();
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
    <script src="backend-integration.js"></script>
    
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
            <a href="?logout=1" class="profile-menu-item" data-action="logout">
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
                    <?php if (!empty($dashboard_data['recent_campaigns'])): ?>
                        <?php foreach (array_slice($dashboard_data['recent_campaigns'], 0, 3) as $campaign): ?>
                            <li class="campaign-item">
                                <a href="#" class="campaign-link">
                                    <span><?php echo htmlspecialchars($campaign['name']); ?></span>
                                    <span class="status-badge <?php echo htmlspecialchars($campaign['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($campaign['status'])); ?>
                                    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
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
                    <?php endif; ?>
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
                                <h3 class="stat-value"><?php echo $dashboard_data['stats']['active_campaigns'] ?? 0; ?></h3>
                                <p class="stat-description">+2 from last month</p>
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
                                <h3 class="stat-value"><?php echo $dashboard_data['stats']['upcoming_events'] ?? 0; ?></h3>
                                <p class="stat-description">3 this week</p>
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
                                <h3 class="stat-value"><?php echo $dashboard_data['stats']['content_items'] ?? 0; ?></h3>
                                <p class="stat-description">+8 this month</p>
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
                                <h3 class="stat-value"><?php echo $dashboard_data['stats']['recent_responses'] ?? 0; ?></h3>
                                <p class="stat-description">+347 this month</p>
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
                                    <?php if (!empty($dashboard_data['recent_campaigns'])): ?>
                                        <?php foreach ($dashboard_data['recent_campaigns'] as $campaign): ?>
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
                            <?php if (!empty($dashboard_data['upcoming_events'])): ?>
                                <?php foreach ($dashboard_data['upcoming_events'] as $event): ?>
                                    <div class="event-item">
                                        <div class="event-date red">
                                            <div class="event-day"><?php echo date('d', strtotime($event['event_date'])); ?></div>
                                            <div class="event-month"><?php echo strtoupper(date('M', strtotime($event['event_date']))); ?></div>
                                        </div>
                                        <div class="event-details">
                                            <h4 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h4>
                                            <p class="event-info"><?php echo htmlspecialchars($event['location']); ?>, <?php echo date('g:i A', strtotime($event['event_date'])); ?></p>
                                            <p class="event-info"><?php echo $event['registered_count']; ?> registered</p>
                                        </div>
                                        <div class="event-actions">
                                            <button class="btn-icon blue" onclick="dashboard.viewEventDetails(<?php echo $event['id']; ?>)" title="View Details">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
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
                            <?php endif; ?>
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
                                <?php if (!empty($dashboard_data['recent_campaigns'])): ?>
                                    <?php foreach ($dashboard_data['recent_campaigns'] as $campaign): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($campaign['name']); ?></td>
                                            <td><?php echo htmlspecialchars($campaign['start_date'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($campaign['end_date'] ?? 'N/A'); ?></td>
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
                                            <h3 class="stat-value"><?php echo $dashboard_data['stats']['content_items'] ?? 47; ?></h3>
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

            <!-- Additional module sections would continue here... -->

            <!-- Audience Segmentation Module -->
            <div id="audience" class="module-section">
                <div class="page-header">
                    <h2 class="page-title">Audience Segmentation</h2>
                    <p class="page-description">Manage your audience segments and outreach campaigns</p>
                </div>
                
                <div class="content-dashboard-layout">
                    <!-- Sidebar for Audience Module -->
                    <aside class="content-sidebar">
                        <div class="nav-item active" data-section="sms-alerts">
                            <i class="fas fa-sms"></i>
                            <span>SMS Alert Notifications</span>
                        </div>
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
                    </aside>
                    
                    <!-- Main Content for Audience Module -->
                    <main class="content-main">
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
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Alert</th>
                                                <th>Segment</th>
                                                <th>Scheduled For</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <div class="font-weight-600">Road Safety Reminder</div>
                                                        <div class="text-muted text-sm">Created: Oct 12, 2023</div>
                                                    </div>
                                                </td>
                                                <td>Urban Residents</td>
                                                <td>Oct 14, 2023<br><span class="text-muted text-sm">10:00 AM</span></td>
                                                <td><span class="status-badge active">Active</span></td>
                                                <td>
                                                    <button class="btn-icon" data-action="view" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn-icon" data-action="edit" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn-icon text-danger" data-action="delete" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </section>
                        
                        <!-- Audience Profiling Section -->
                        <section id="audience-section-profiling" class="hidden">
                            <div class="page-header">
                                <h1 class="page-title">Audience Profiling</h1>
                                <p class="page-description">Analyze and understand your audience demographics and preferences</p>
                            </div>
                            
                            <div class="stats-grid mb-20">
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <div class="stat-info">
                                            <p class="stat-label">Total Profiles</p>
                                            <h3 class="stat-value">2,847</h3>
                                        </div>
                                        <div class="stat-icon blue">
                                            <i class="fas fa-users"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <div class="stat-info">
                                            <p class="stat-label">Active Segments</p>
                                            <h3 class="stat-value">12</h3>
                                        </div>
                                        <div class="stat-icon green">
                                            <i class="fas fa-chart-pie"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <div class="stat-info">
                                            <p class="stat-label">Engagement Rate</p>
                                            <h3 class="stat-value">24.7%</h3>
                                        </div>
                                        <div class="stat-icon purple">
                                            <i class="fas fa-chart-line"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mb-20">
                                <div class="card-header">
                                    <h3 class="card-title">Demographic Analysis</h3>
                                </div>
                                <div class="chart-wrapper">
                                    <canvas id="demographicsChart"></canvas>
                                </div>
                            </div>
                            
                            <div class="dashboard-content">
                                <div class="dashboard-card">
                                    <div class="card-header">
                                        <h3 class="card-title">Age Distribution</h3>
                                    </div>
                                    <div class="chart-wrapper">
                                        <canvas id="ageDistributionChart"></canvas>
                                    </div>
                                </div>
                                
                                <div class="dashboard-card">
                                    <div class="card-header">
                                        <h3 class="card-title">Geographic Distribution</h3>
                                    </div>
                                    <div class="chart-wrapper">
                                        <canvas id="geoDistributionChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </section>
                        
                        <!-- Segmentation Tools Section -->
                        <section id="audience-section-segmentation" class="hidden">
                            <div class="page-header">
                                <h1 class="page-title">Segmentation Tools</h1>
                                <p class="page-description">Create and manage audience segments for targeted campaigns</p>
                            </div>
                            
                            <div class="card mb-20">
                                <div class="card-header">
                                    <h3 class="card-title">Create New Segment</h3>
                                    <button class="btn-primary" id="createSegmentBtn">
                                        <i class="fas fa-plus"></i> New Segment
                                    </button>
                                </div>
                                
                                <form id="segmentForm" class="card-section">
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label class="form-label">Segment Name</label>
                                            <input type="text" class="form-input" name="segmentName" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Campaign Association</label>
                                            <select class="form-select" name="campaign">
                                                <option value="">Select Campaign</option>
                                                <option value="road-safety">Road Safety Campaign</option>
                                                <option value="cyber-security">Cyber Security Campaign</option>
                                                <option value="fire-prevention">Fire Prevention Campaign</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Segmentation Criteria</label>
                                        <div class="criteria-builder">
                                            <div class="criteria-item">
                                                <select class="form-select" name="criteria-field">
                                                    <option value="age">Age</option>
                                                    <option value="location">Location</option>
                                                    <option value="interest">Interest</option>
                                                    <option value="engagement">Engagement Level</option>
                                                </select>
                                                <select class="form-select" name="criteria-operator">
                                                    <option value="equals">Equals</option>
                                                    <option value="contains">Contains</option>
                                                    <option value="greater">Greater than</option>
                                                    <option value="less">Less than</option>
                                                </select>
                                                <input type="text" class="form-input" name="criteria-value" placeholder="Value">
                                                <button type="button" class="btn-icon red" onclick="removeCriteria(this)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <button type="button" class="btn-text" id="addCriteriaBtn">
                                            <i class="fas fa-plus"></i> Add Criteria
                                        </button>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="button" class="btn-secondary">Cancel</button>
                                        <button type="submit" class="btn-primary">Create Segment</button>
                                    </div>
                                </form>
                            </div>
                            
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Existing Segments</h3>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Segment Name</th>
                                                <th>Size</th>
                                                <th>Campaign</th>
                                                <th>Last Updated</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Urban Residents</td>
                                                <td>1,247 contacts</td>
                                                <td>Road Safety Campaign</td>
                                                <td>Oct 15, 2023</td>
                                                <td>
                                                    <button class="btn-icon blue"><i class="fas fa-eye"></i></button>
                                                    <button class="btn-icon green"><i class="fas fa-edit"></i></button>
                                                    <button class="btn-icon red"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Senior Citizens</td>
                                                <td>892 contacts</td>
                                                <td>Cyber Security Campaign</td>
                                                <td>Oct 12, 2023</td>
                                                <td>
                                                    <button class="btn-icon blue"><i class="fas fa-eye"></i></button>
                                                    <button class="btn-icon green"><i class="fas fa-edit"></i></button>
                                                    <button class="btn-icon red"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </section>
                        
                        <!-- Personal Outreach Section -->
                        <section id="audience-section-personal-outreach" class="hidden">
                            <div class="page-header">
                                <h1 class="page-title">Personal Outreach</h1>
                                <p class="page-description">Manage one-on-one communications and personalized campaigns</p>
                            </div>
                            
                            <div class="card mb-20">
                                <div class="card-header">
                                    <h3 class="card-title">Send Personal Message</h3>
                                </div>
                                
                                <form id="personalOutreachForm" class="card-section">
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label class="form-label">Recipient</label>
                                            <select class="form-select" name="recipient" required>
                                                <option value="">Select recipient</option>
                                                <option value="individual">Individual Contact</option>
                                                <option value="segment">Audience Segment</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Communication Method</label>
                                            <select class="form-select" name="method" required>
                                                <option value="email">Email</option>
                                                <option value="sms">SMS</option>
                                                <option value="phone">Phone Call</option>
                                                <option value="letter">Physical Letter</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Subject/Title</label>
                                        <input type="text" class="form-input" name="subject" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Message</label>
                                        <textarea class="form-textarea" name="message" rows="6" required></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Personalization Tags</label>
                                        <div class="tag-buttons">
                                            <button type="button" class="btn-tag" onclick="insertTag('[FIRST_NAME]')">[FIRST_NAME]</button>
                                            <button type="button" class="btn-tag" onclick="insertTag('[LAST_NAME]')">[LAST_NAME]</button>
                                            <button type="button" class="btn-tag" onclick="insertTag('[LOCATION]')">[LOCATION]</button>
                                            <button type="button" class="btn-tag" onclick="insertTag('[CAMPAIGN_NAME]')">[CAMPAIGN_NAME]</button>
                                        </div>
                                    </div>
                                    
                                    <div class="form-actions">
                                        <button type="button" class="btn-secondary">Save Draft</button>
                                        <button type="submit" class="btn-primary">Send Message</button>
                                    </div>
                                </form>
                            </div>
                            
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Recent Outreach</h3>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Recipient</th>
                                                <th>Method</th>
                                                <th>Subject</th>
                                                <th>Sent Date</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Urban Residents Segment</td>
                                                <td>Email</td>
                                                <td>Road Safety Tips</td>
                                                <td>Oct 20, 2023</td>
                                                <td><span class="status-badge completed">Delivered</span></td>
                                                <td>
                                                    <button class="btn-icon blue"><i class="fas fa-eye"></i></button>
                                                    <button class="btn-icon green"><i class="fas fa-chart-line"></i></button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </section>
                        
                        <!-- Audience Lists Section -->
                        <section id="audience-section-lists" class="hidden">
                            <div class="page-header">
                                <h1 class="page-title">Audience Lists</h1>
                                <p class="page-description">Manage and organize your contact lists and audience databases</p>
                            </div>
                            
                            <div class="card mb-20">
                                <div class="card-header">
                                    <h3 class="card-title">Import/Export Contacts</h3>
                                </div>
                                
                                <div class="card-section">
                                    <div class="import-export-grid">
                                        <div class="import-section">
                                            <h4>Import Contacts</h4>
                                            <div class="file-upload-area" id="contacts-upload-area">
                                                <i class="fas fa-upload"></i>
                                                <p>Upload CSV file with contacts</p>
                                                <input type="file" accept=".csv" id="contacts-file-input">
                                            </div>
                                            <button class="btn-primary" id="importContactsBtn">Import Contacts</button>
                                        </div>
                                        
                                        <div class="export-section">
                                            <h4>Export Contacts</h4>
                                            <div class="form-group">
                                                <label class="form-label">Select Lists to Export</label>
                                                <select class="form-select" multiple>
                                                    <option value="all">All Contacts</option>
                                                    <option value="urban">Urban Residents</option>
                                                    <option value="seniors">Senior Citizens</option>
                                                    <option value="emergency">Emergency Response Team</option>
                                                </select>
                                            </div>
                                            <button class="btn-secondary" id="exportContactsBtn">Export as CSV</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Contact Lists</h3>
                                    <button class="btn-primary" id="newListBtn">
                                        <i class="fas fa-plus"></i> New List
                                    </button>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>List Name</th>
                                                <th>Contacts</th>
                                                <th>Last Updated</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Urban Residents</td>
                                                <td>1,247</td>
                                                <td>Oct 20, 2023</td>
                                                <td><span class="status-badge active">Active</span></td>
                                                <td>
                                                    <button class="btn-icon blue"><i class="fas fa-eye"></i></button>
                                                    <button class="btn-icon green"><i class="fas fa-edit"></i></button>
                                                    <button class="btn-icon purple"><i class="fas fa-download"></i></button>
                                                    <button class="btn-icon red"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Senior Citizens</td>
                                                <td>892</td>
                                                <td>Oct 15, 2023</td>
                                                <td><span class="status-badge active">Active</span></td>
                                                <td>
                                                    <button class="btn-icon blue"><i class="fas fa-eye"></i></button>
                                                    <button class="btn-icon green"><i class="fas fa-edit"></i></button>
                                                    <button class="btn-icon purple"><i class="fas fa-download"></i></button>
                                                    <button class="btn-icon red"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Emergency Response Team</td>
                                                <td>156</td>
                                                <td>Oct 18, 2023</td>
                                                <td><span class="status-badge active">Active</span></td>
                                                <td>
                                                    <button class="btn-icon blue"><i class="fas fa-eye"></i></button>
                                                    <button class="btn-icon green"><i class="fas fa-edit"></i></button>
                                                    <button class="btn-icon purple"><i class="fas fa-download"></i></button>
                                                    <button class="btn-icon red"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
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
                    <p class="page-description">Plan, schedule, and manage public safety events and workshops</p>
                </div>
                
                <div class="content-dashboard-layout">
                    <!-- Sidebar for Event Management Module -->
                    <aside class="content-sidebar">
                        <div class="nav-item active" data-section="events-dashboard">
                            <i class="fas fa-chart-line"></i>
                            <span>Events Dashboard</span>
                        </div>
                        <div class="nav-item" data-section="create-event">
                            <i class="fas fa-plus-circle"></i>
                            <span>Create Event</span>
                        </div>
                        <div class="nav-item" data-section="event-calendar">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Event Calendar</span>
                        </div>
                        <div class="nav-item" data-section="registrations">
                            <i class="fas fa-user-check"></i>
                            <span>Registrations</span>
                        </div>
                        <div class="nav-item" data-section="venues">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Venue Management</span>
                        </div>
                        
                        <div class="stats mt-20">
                            <h3>Event Stats</h3>
                            <div class="stat-item">
                                <span>Total Events</span>
                                <span><?php echo $dashboard_data['stats']['upcoming_events'] ?? 8; ?></span>
                            </div>
                            <div class="stat-item">
                                <span>This Month</span>
                                <span>3</span>
                            </div>
                            <div class="stat-item">
                                <span>Total Registrations</span>
                                <span>284</span>
                            </div>
                        </div>
                    </aside>
                    
                    <!-- Main Content for Event Management Module -->
                    <main class="content-main">
                        <!-- Events Dashboard Section -->
                        <section id="events-section-events-dashboard">
                            <div class="page-header">
                                <h1 class="page-title">Events Dashboard</h1>
                                <p class="page-description">Overview of your upcoming and past events</p>
                            </div>
                            
                            <div class="stats-grid mb-20">
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <div class="stat-info">
                                            <p class="stat-label">Upcoming Events</p>
                                            <h3 class="stat-value"><?php echo $dashboard_data['stats']['upcoming_events'] ?? 5; ?></h3>
                                        </div>
                                        <div class="stat-icon blue">
                                            <i class="fas fa-calendar-check"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <div class="stat-info">
                                            <p class="stat-label">Total Registrations</p>
                                            <h3 class="stat-value">284</h3>
                                        </div>
                                        <div class="stat-icon green">
                                            <i class="fas fa-user-check"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <div class="stat-info">
                                            <p class="stat-label">Active Venues</p>
                                            <h3 class="stat-value">12</h3>
                                        </div>
                                        <div class="stat-icon purple">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mb-20">
                                <div class="card-header">
                                    <h3 class="card-title">Upcoming Events</h3>
                                    <button class="btn-primary" onclick="switchEventSection('create-event')">
                                        <i class="fas fa-plus"></i> New Event
                                    </button>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Event</th>
                                                <th>Date & Time</th>
                                                <th>Venue</th>
                                                <th>Registered</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($dashboard_data['upcoming_events'])): ?>
                                                <?php foreach ($dashboard_data['upcoming_events'] as $event): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                                                        <td><?php echo date('M d, Y g:i A', strtotime($event['event_date'])); ?></td>
                                                        <td><?php echo htmlspecialchars($event['location']); ?></td>
                                                        <td><?php echo $event['registered_count']; ?></td>
                                                        <td><span class="status-badge active">Active</span></td>
                                                        <td>
                                                            <button class="btn-icon blue"><i class="fas fa-eye"></i></button>
                                                            <button class="btn-icon green"><i class="fas fa-edit"></i></button>
                                                            <button class="btn-icon red"><i class="fas fa-trash"></i></button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td>Road Safety Workshop</td>
                                                    <td>Oct 15, 2023 10:00 AM</td>
                                                    <td>City Community Center</td>
                                                    <td>45/50</td>
                                                    <td><span class="status-badge active">Active</span></td>
                                                    <td>
                                                        <button class="btn-icon blue"><i class="fas fa-eye"></i></button>
                                                        <button class="btn-icon green"><i class="fas fa-edit"></i></button>
                                                        <button class="btn-icon red"><i class="fas fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Cybersecurity Seminar</td>
                                                    <td>Oct 22, 2023 2:00 PM</td>
                                                    <td>Public Library Auditorium</td>
                                                    <td>67/80</td>
                                                    <td><span class="status-badge active">Active</span></td>
                                                    <td>
                                                        <button class="btn-icon blue"><i class="fas fa-eye"></i></button>
                                                        <button class="btn-icon green"><i class="fas fa-edit"></i></button>
                                                        <button class="btn-icon red"><i class="fas fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Event Performance</h3>
                                </div>
                                <div class="chart-wrapper">
                                    <canvas id="eventPerformanceChart"></canvas>
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
                        <div class="nav-item active" data-section="surveys-dashboard">
                            <i class="fas fa-chart-line"></i>
                            <span>Surveys Dashboard</span>
                        </div>
                        <div class="nav-item" data-section="create-survey">
                            <i class="fas fa-plus-circle"></i>
                            <span>Create Survey</span>
                        </div>
                        <div class="nav-item" data-section="responses">
                            <i class="fas fa-comments"></i>
                            <span>View Responses</span>
                        </div>
                        <div class="nav-item" data-section="analytics">
                            <i class="fas fa-chart-bar"></i>
                            <span>Analytics</span>
                        </div>
                        
                        <div class="stats mt-20">
                            <h3>Survey Stats</h3>
                            <div class="stat-item">
                                <span>Total Surveys</span>
                                <span>15</span>
                            </div>
                            <div class="stat-item">
                                <span>Active Surveys</span>
                                <span>4</span>
                            </div>
                            <div class="stat-item">
                                <span>Total Responses</span>
                                <span><?php echo $dashboard_data['stats']['recent_responses'] ?? 892; ?></span>
                            </div>
                        </div>
                    </aside>
                    
                    <!-- Main Content for Feedback Module -->
                    <main class="content-main">
                        <!-- Surveys Dashboard Section -->
                        <section id="feedback-section-surveys-dashboard">
                            <div class="page-header">
                                <h1 class="page-title">Surveys Dashboard</h1>
                                <p class="page-description">Overview of your survey campaigns and responses</p>
                            </div>
                            
                            <div class="stats-grid mb-20">
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <div class="stat-info">
                                            <p class="stat-label">Active Surveys</p>
                                            <h3 class="stat-value">4</h3>
                                        </div>
                                        <div class="stat-icon blue">
                                            <i class="fas fa-poll"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <div class="stat-info">
                                            <p class="stat-label">Total Responses</p>
                                            <h3 class="stat-value"><?php echo $dashboard_data['stats']['recent_responses'] ?? 892; ?></h3>
                                        </div>
                                        <div class="stat-icon green">
                                            <i class="fas fa-comment-dots"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <div class="stat-info">
                                            <p class="stat-label">Response Rate</p>
                                            <h3 class="stat-value">68%</h3>
                                        </div>
                                        <div class="stat-icon purple">
                                            <i class="fas fa-percentage"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card mb-20">
                                <div class="card-header">
                                    <h3 class="card-title">Recent Surveys</h3>
                                    <button class="btn-primary" onclick="switchFeedbackSection('create-survey')">
                                        <i class="fas fa-plus"></i> New Survey
                                    </button>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Survey Title</th>
                                                <th>Created</th>
                                                <th>Responses</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Road Safety Awareness Survey</td>
                                                <td>Oct 15, 2023</td>
                                                <td>247</td>
                                                <td><span class="status-badge active">Active</span></td>
                                                <td>
                                                    <button class="btn-icon blue"><i class="fas fa-eye"></i></button>
                                                    <button class="btn-icon green"><i class="fas fa-chart-bar"></i></button>
                                                    <button class="btn-icon red"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Cybersecurity Knowledge Check</td>
                                                <td>Oct 10, 2023</td>
                                                <td>189</td>
                                                <td><span class="status-badge completed">Closed</span></td>
                                                <td>
                                                    <button class="btn-icon blue"><i class="fas fa-eye"></i></button>
                                                    <button class="btn-icon green"><i class="fas fa-chart-bar"></i></button>
                                                    <button class="btn-icon red"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Response Trends</h3>
                                </div>
                                <div class="chart-wrapper">
                                    <canvas id="responseTrendsChart"></canvas>
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
                    <p class="page-description">Track and analyze the impact of your campaigns</p>
                </div>
                
                <div class="content-dashboard-layout">
                    <!-- Sidebar for Impact Monitoring Module -->
                    <aside class="content-sidebar">
                        <div class="nav-item active" data-section="impact-dashboard">
                            <i class="fas fa-chart-line"></i>
                            <span>Impact Dashboard</span>
                        </div>
                        <div class="nav-item" data-section="kpi-tracking">
                            <i class="fas fa-bullseye"></i>
                            <span>KPI Tracking</span>
                        </div>
                        <div class="nav-item" data-section="reports">
                            <i class="fas fa-file-alt"></i>
                            <span>Reports</span>
                        </div>
                        <div class="nav-item" data-section="comparisons">
                            <i class="fas fa-balance-scale"></i>
                            <span>Comparisons</span>
                        </div>
                        
                        <div class="stats mt-20">
                            <h3>Monitoring Stats</h3>
                            <div class="stat-item">
                                <span>Active KPIs</span>
                                <span>24</span>
                            </div>
                            <div class="stat-item">
                                <span>Reports Generated</span>
                                <span>18</span>
                            </div>
                            <div class="stat-item">
                                <span>Data Points</span>
                                <span>1.2K</span>
                            </div>
                        </div>
                    </aside>
                    
                    <!-- Main Content for Impact Monitoring Module -->
                    <main class="content-main">
                        <!-- Impact Dashboard Section -->
                        <section id="monitoring-section-impact-dashboard">
                            <div class="page-header">
                                <h1 class="page-title">Impact Dashboard</h1>
                                <p class="page-description">Monitor the effectiveness of your safety campaigns</p>
                            </div>
                            
                            <div class="stats-grid mb-20">
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <div class="stat-info">
                                            <p class="stat-label">Campaign Reach</p>
                                            <h3 class="stat-value">12.4K</h3>
                                            <p class="stat-description">+15% from last month</p>
                                        </div>
                                        <div class="stat-icon blue">
                                            <i class="fas fa-users"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <div class="stat-info">
                                            <p class="stat-label">Engagement Rate</p>
                                            <h3 class="stat-value">68%</h3>
                                            <p class="stat-description">+5% from last month</p>
                                        </div>
                                        <div class="stat-icon green">
                                            <i class="fas fa-chart-line"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <div class="stat-info">
                                            <p class="stat-label">Behavior Change</p>
                                            <h3 class="stat-value">43%</h3>
                                            <p class="stat-description">Based on surveys</p>
                                        </div>
                                        <div class="stat-icon purple">
                                            <i class="fas fa-exchange-alt"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <div class="stat-info">
                                            <p class="stat-label">ROI Score</p>
                                            <h3 class="stat-value">4.2x</h3>
                                            <p class="stat-description">Return on investment</p>
                                        </div>
                                        <div class="stat-icon yellow">
                                            <i class="fas fa-dollar-sign"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="dashboard-content">
                                <div class="dashboard-card large">
                                    <div class="card-header">
                                        <h3 class="card-title">Campaign Impact Over Time</h3>
                                        <select class="form-select form-select-sm">
                                            <option>Last 30 days</option>
                                            <option>Last 90 days</option>
                                            <option>Last year</option>
                                        </select>
                                    </div>
                                    <div class="chart-wrapper">
                                        <canvas id="impactOverTimeChart"></canvas>
                                    </div>
                                </div>
                                
                                <div class="dashboard-card large">
                                    <div class="card-header">
                                        <h3 class="card-title">Impact by Category</h3>
                                    </div>
                                    <div class="chart-wrapper">
                                        <canvas id="impactByCategoryChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </main>
                </div>
            </div>
            
            <!-- Collaboration Module -->
            <div id="collaboration" class="module-section">
                <div class="page-header">
                    <h2 class="page-title">Collaboration</h2>
                    <p class="page-description">Work together with your team and partners</p>
                </div>
                
                <div class="content-dashboard-layout">
                    <!-- Sidebar for Collaboration Module -->
                    <aside class="content-sidebar">
                        <div class="nav-item active" data-section="team-dashboard">
                            <i class="fas fa-users"></i>
                            <span>Team Dashboard</span>
                        </div>
                        <div class="nav-item" data-section="projects">
                            <i class="fas fa-project-diagram"></i>
                            <span>Projects</span>
                        </div>
                        <div class="nav-item" data-section="communications">
                            <i class="fas fa-comments"></i>
                            <span>Communications</span>
                        </div>
                        <div class="nav-item" data-section="resources">
                            <i class="fas fa-share-alt"></i>
                            <span>Shared Resources</span>
                        </div>
                        
                        <div class="stats mt-20">
                            <h3>Team Stats</h3>
                            <div class="stat-item">
                                <span>Team Members</span>
                                <span>12</span>
                            </div>
                            <div class="stat-item">
                                <span>Active Projects</span>
                                <span>8</span>
                            </div>
                            <div class="stat-item">
                                <span>Shared Files</span>
                                <span>47</span>
                            </div>
                        </div>
                    </aside>
                    
                    <!-- Main Content for Collaboration Module -->
                    <main class="content-main">
                        <!-- Team Dashboard Section -->
                        <section id="collaboration-section-team-dashboard">
                            <div class="page-header">
                                <h1 class="page-title">Team Dashboard</h1>
                                <p class="page-description">Collaborate effectively with your team members</p>
                            </div>
                            
                            <div class="stats-grid mb-20">
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <div class="stat-info">
                                            <p class="stat-label">Team Members</p>
                                            <h3 class="stat-value">12</h3>
                                        </div>
                                        <div class="stat-icon blue">
                                            <i class="fas fa-users"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <div class="stat-info">
                                            <p class="stat-label">Active Projects</p>
                                            <h3 class="stat-value">8</h3>
                                        </div>
                                        <div class="stat-icon green">
                                            <i class="fas fa-project-diagram"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-content">
                                        <div class="stat-info">
                                            <p class="stat-label">Messages Today</p>
                                            <h3 class="stat-value">47</h3>
                                        </div>
                                        <div class="stat-icon purple">
                                            <i class="fas fa-comments"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="dashboard-content">
                                <div class="dashboard-card">
                                    <div class="card-header">
                                        <h3 class="card-title">Team Members</h3>
                                        <button class="btn-primary" onclick="inviteTeamMember()">
                                            <i class="fas fa-user-plus"></i> Invite Member
                                        </button>
                                    </div>
                                    
                                    <div class="team-members-grid">
                                        <div class="team-member-card">
                                            <div class="member-avatar">JS</div>
                                            <div class="member-info">
                                                <h4>John Smith</h4>
                                                <p>Campaign Manager</p>
                                                <span class="status-badge active">Online</span>
                                            </div>
                                        </div>
                                        <div class="team-member-card">
                                            <div class="member-avatar">SJ</div>
                                            <div class="member-info">
                                                <h4>Sarah Johnson</h4>
                                                <p>Content Creator</p>
                                                <span class="status-badge planning">Away</span>
                                            </div>
                                        </div>
                                        <div class="team-member-card">
                                            <div class="member-avatar">MB</div>
                                            <div class="member-info">
                                                <h4>Mike Brown</h4>
                                                <p>Data Analyst</p>
                                                <span class="status-badge active">Online</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="dashboard-card">
                                    <div class="card-header">
                                        <h3 class="card-title">Recent Activity</h3>
                                    </div>
                                    
                                    <div class="activity-feed">
                                        <div class="activity-item">
                                            <div class="activity-avatar">JS</div>
                                            <div class="activity-content">
                                                <p><strong>John Smith</strong> updated the Road Safety Campaign</p>
                                                <span class="activity-time">2 hours ago</span>
                                            </div>
                                        </div>
                                        <div class="activity-item">
                                            <div class="activity-avatar">SJ</div>
                                            <div class="activity-content">
                                                <p><strong>Sarah Johnson</strong> uploaded new content</p>
                                                <span class="activity-time">4 hours ago</span>
                                            </div>
                                        </div>
                                        <div class="activity-item">
                                            <div class="activity-avatar">MB</div>
                                            <div class="activity-content">
                                                <p><strong>Mike Brown</strong> generated impact report</p>
                                                <span class="activity-time">6 hours ago</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </main>
                </div>
            </div>
            <!-- For brevity, I'll include just the closing tags -->
        </div>
    </div>

    <!-- Load JavaScript -->
    <script src="index.js"></script>
    <script>
        // Pass PHP data to JavaScript
        window.dashboardData = <?php echo json_encode($dashboard_data); ?>;
        window.userData = {
            name: '<?php echo addslashes($user_name); ?>',
            email: '<?php echo addslashes($user_email); ?>',
            role: '<?php echo addslashes($user_role); ?>',
            avatar: '<?php echo addslashes($user_avatar); ?>'
        };
        
        // Initialize dashboard with PHP data
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof dashboard !== 'undefined') {
                dashboard.loadDashboardData(window.dashboardData);
            }
        });
    </script>