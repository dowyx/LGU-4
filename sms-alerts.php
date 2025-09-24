<?php
/**
 * SMS Alerts Management - PHP Interface
 * Public Safety Campaign Management System
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'backend/config/database.php';

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if (isset($_POST['create_sms_alert'])) {
            // Validate message length
            if (strlen($_POST['message']) > 160) {
                throw new Exception('SMS message cannot exceed 160 characters');
            }
            
            // Create new SMS alert
            $stmt = $db->prepare("
                INSERT INTO sms_alerts (title, message, alert_type, audience_segment_id, campaign_id, scheduled_date, scheduled_time, priority, status, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?)
            ");
            
            $stmt->execute([
                $_POST['title'], $_POST['message'], $_POST['alert_type'], 
                $_POST['audience_segment_id'] ?: null, $_POST['campaign_id'] ?: null,
                $_POST['scheduled_date'] ?: null, $_POST['scheduled_time'] ?: null,
                $_POST['priority'], $_SESSION['user_id']
            ]);
            
            $message = "SMS Alert created successfully!";
            
        } elseif (isset($_POST['send_now'])) {
            // Send SMS immediately
            $alertId = $_POST['alert_id'];
            
            // Update status to sending
            $stmt = $db->prepare("UPDATE sms_alerts SET status = 'sending', sent_at = NOW() WHERE id = ?");
            $stmt->execute([$alertId]);
            
            // Simulate sending process (in real implementation, integrate with SMS provider)
            $stmt = $db->prepare("
                UPDATE sms_alerts SET 
                    status = 'sent', 
                    sent_count = 150, 
                    delivered_count = 147, 
                    failed_count = 3,
                    delivery_rate = 98.00,
                    total_cost = 7.50
                WHERE id = ?
            ");
            $stmt->execute([$alertId]);
            
            $message = "SMS Alert sent successfully to 150 recipients!";
        }
        
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch data for display
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get SMS alerts
    $alertsStmt = $db->query("
        SELECT sa.*, 
               u.username as created_by_name,
               c.title as campaign_title,
               aud.name as segment_name
        FROM sms_alerts sa
        LEFT JOIN users u ON sa.created_by = u.id
        LEFT JOIN campaigns c ON sa.campaign_id = c.id
        LEFT JOIN audience_segments aud ON sa.audience_segment_id = aud.id
        ORDER BY sa.created_at DESC 
        LIMIT 50
    ");
    $alerts = $alertsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get segments for dropdown
    $segmentsStmt = $db->query("
        SELECT id, name, 
               (SELECT COUNT(*) FROM segment_contacts WHERE segment_id = audience_segments.id) as contact_count
        FROM audience_segments 
        WHERE status = 'active'
        ORDER BY name
    ");
    $segments = $segmentsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get campaigns for dropdown
    $campaignsStmt = $db->query("
        SELECT id, title 
        FROM campaigns 
        WHERE status = 'active' 
        ORDER BY title
    ");
    $campaigns = $campaignsStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $alerts = [];
    $segments = [];
    $campaigns = [];
    $error = "Database error: " . $e->getMessage();
}

// SMS Templates
$templates = [
    'emergency' => 'EMERGENCY ALERT: {message}. Follow safety protocols immediately. More info: {url}',
    'warning' => 'WARNING: {message}. Please take necessary precautions. Info: {url}',
    'reminder' => 'Reminder: {event_name} on {date} at {time}. Location: {location}. Reply STOP to opt out.',
    'info' => 'SAFETY TIP: {tip}. Stay safe! More tips: {url}',
    'update' => 'UPDATE: {message}. For more information visit: {url}'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMS Alerts - Public Safety Campaign</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .tabs {
            display: flex;
            background: #f8f9fa;
            border-radius: 6px;
            padding: 5px;
            margin: 20px 0;
        }
        .tab {
            flex: 1;
            text-align: center;
            padding: 12px;
            background: transparent;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s;
        }
        .tab.active {
            background: #e74c3c;
            color: white;
        }
        .tab:hover {
            background: rgba(231, 76, 60, 0.1);
        }
        .tab.active:hover {
            background: #c0392b;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .alert-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid #e74c3c;
        }
        .alert-card.draft { border-left-color: #f39c12; }
        .alert-card.scheduled { border-left-color: #3498db; }
        .alert-card.sent { border-left-color: #27ae60; }
        .alert-card.failed { border-left-color: #e74c3c; }
        
        .alert-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .alert-title {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }
        .alert-status {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-draft { background: #f39c12; color: white; }
        .status-scheduled { background: #3498db; color: white; }
        .status-sent { background: #27ae60; color: white; }
        .status-failed { background: #e74c3c; color: white; }
        
        .alert-message {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
            font-family: monospace;
            border-left: 3px solid #3498db;
        }
        .alert-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 10px 0;
            font-size: 14px;
            color: #7f8c8d;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        
        .btn:hover {
            opacity: 0.8;
            transform: translateY(-2px);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        .form-group {
            margin: 15px 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .char-counter {
            text-align: right;
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        .char-counter.warning {
            color: #f39c12;
        }
        .char-counter.danger {
            color: #e74c3c;
        }
        .message {
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .navigation {
            background: #2c3e50;
            padding: 15px 0;
            margin-bottom: 30px;
        }
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        .nav-links {
            display: flex;
            gap: 20px;
        }
        .nav-link {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .nav-link:hover {
            background: rgba(255,255,255,0.1);
        }
        .user-info {
            color: white;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .template-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .template-card:hover {
            border-color: #3498db;
            transform: translateY(-2px);
        }
        .template-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .template-message {
            font-size: 14px;
            color: #7f8c8d;
            font-family: monospace;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <nav class="navigation">
        <div class="nav-container">
            <div class="nav-links">
                <a href="dashboard.php" class="nav-link">🏠 Dashboard</a>
                <a href="campaigns.php" class="nav-link">📢 Campaigns</a>
                <a href="audience.php" class="nav-link">👥 Audience</a>
                <a href="sms-alerts.php" class="nav-link">📱 SMS Alerts</a>
                <a href="surveys.php" class="nav-link">📝 Surveys</a>
                <a href="users.php" class="nav-link">👤 Users</a>
            </div>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
                <a href="logout.php" class="nav-link">🚪 Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1>📱 SMS Alert Notifications</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab active" onclick="showTab('alerts')">📋 All Alerts (<?php echo count($alerts); ?>)</button>
            <button class="tab" onclick="showTab('create')">➕ Create Alert</button>
            <button class="tab" onclick="showTab('templates')">📝 Templates</button>
            <button class="tab" onclick="showTab('analytics')">📊 Analytics</button>
        </div>

        <!-- Alerts List Tab -->
        <div id="alerts" class="tab-content active">
            <div class="btn-group">
                <button onclick="filterAlerts('all')" class="btn btn-primary">All</button>
                <button onclick="filterAlerts('draft')" class="btn btn-warning">Draft</button>
                <button onclick="filterAlerts('scheduled')" class="btn btn-primary">Scheduled</button>
                <button onclick="filterAlerts('sent')" class="btn btn-success">Sent</button>
                <button onclick="refreshAlerts()" class="btn btn-primary">🔄 Refresh</button>
            </div>

            <?php foreach ($alerts as $alert): ?>
                <div class="alert-card <?php echo $alert['status']; ?>">
                    <div class="alert-header">
                        <div class="alert-title"><?php echo htmlspecialchars($alert['title']); ?></div>
                        <div class="alert-status status-<?php echo $alert['status']; ?>">
                            <?php echo strtoupper($alert['status']); ?>
                        </div>
                    </div>
                    
                    <div class="alert-message">
                        <?php echo htmlspecialchars($alert['message']); ?>
                    </div>
                    
                    <div class="alert-meta">
                        <div><strong>Type:</strong> <?php echo ucfirst($alert['alert_type']); ?></div>
                        <div><strong>Priority:</strong> <?php echo ucfirst($alert['priority']); ?></div>
                        <div><strong>Segment:</strong> <?php echo htmlspecialchars($alert['segment_name'] ?? 'None'); ?></div>
                        <div><strong>Campaign:</strong> <?php echo htmlspecialchars($alert['campaign_title'] ?? 'None'); ?></div>
                        <?php if ($alert['sent_count'] > 0): ?>
                            <div><strong>Sent:</strong> <?php echo $alert['sent_count']; ?></div>
                            <div><strong>Delivered:</strong> <?php echo $alert['delivered_count']; ?></div>
                            <div><strong>Delivery Rate:</strong> <?php echo $alert['delivery_rate']; ?>%</div>
                        <?php endif; ?>
                        <?php if ($alert['scheduled_date']): ?>
                            <div><strong>Scheduled:</strong> <?php echo $alert['scheduled_date'] . ' ' . $alert['scheduled_time']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="btn-group">
                        <?php if ($alert['status'] === 'draft'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                                <button type="submit" name="send_now" class="btn btn-danger" 
                                        onclick="return confirm('Send this SMS alert now?')">🚀 Send Now</button>
                            </form>
                            <button onclick="scheduleAlert(<?php echo $alert['id']; ?>)" class="btn btn-primary">⏰ Schedule</button>
                        <?php endif; ?>
                        
                        <?php if ($alert['status'] === 'scheduled'): ?>
                            <button onclick="cancelAlert(<?php echo $alert['id']; ?>)" class="btn btn-warning">❌ Cancel</button>
                            <button onclick="rescheduleAlert(<?php echo $alert['id']; ?>)" class="btn btn-primary">⏰ Reschedule</button>
                        <?php endif; ?>
                        
                        <button onclick="viewDetails(<?php echo $alert['id']; ?>)" class="btn btn-primary">👁️ Details</button>
                        
                        <?php if ($alert['status'] === 'draft'): ?>
                            <button onclick="editAlert(<?php echo $alert['id']; ?>)" class="btn btn-primary">✏️ Edit</button>
                            <button onclick="deleteAlert(<?php echo $alert['id']; ?>)" class="btn btn-danger">🗑️ Delete</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Create Alert Tab -->
        <div id="create" class="tab-content">
            <h2>➕ Create New SMS Alert</h2>
            
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Alert Title *</label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>Alert Type *</label>
                        <select name="alert_type" required>
                            <option value="">Select Type</option>
                            <option value="emergency">🚨 Emergency</option>
                            <option value="warning">⚠️ Warning</option>
                            <option value="info">ℹ️ Information</option>
                            <option value="reminder">🔔 Reminder</option>
                            <option value="update">📢 Update</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Target Audience</label>
                        <select name="audience_segment_id">
                            <option value="">Select Segment</option>
                            <?php foreach ($segments as $segment): ?>
                                <option value="<?php echo $segment['id']; ?>">
                                    <?php echo htmlspecialchars($segment['name']); ?> (<?php echo $segment['contact_count']; ?> contacts)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Related Campaign</label>
                        <select name="campaign_id">
                            <option value="">Select Campaign</option>
                            <?php foreach ($campaigns as $campaign): ?>
                                <option value="<?php echo $campaign['id']; ?>">
                                    <?php echo htmlspecialchars($campaign['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Priority</label>
                        <select name="priority">
                            <option value="low">🟢 Low</option>
                            <option value="normal" selected>🟡 Normal</option>
                            <option value="high">🟠 High</option>
                            <option value="urgent">🔴 Urgent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Schedule Date (Optional)</label>
                        <input type="date" name="scheduled_date">
                    </div>
                    <div class="form-group">
                        <label>Schedule Time (Optional)</label>
                        <input type="time" name="scheduled_time">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>SMS Message * (Max 160 characters)</label>
                    <textarea name="message" id="sms_message" rows="4" maxlength="160" required 
                              placeholder="Enter your SMS message here..."></textarea>
                    <div class="char-counter" id="char_counter">160 characters remaining</div>
                </div>
                
                <button type="submit" name="create_sms_alert" class="btn btn-success">✅ Create SMS Alert</button>
            </form>
        </div>

        <!-- Templates Tab -->
        <div id="templates" class="tab-content">
            <h2>📝 SMS Message Templates</h2>
            <p>Click on a template to use it in your SMS alert:</p>
            
            <div class="template-grid">
                <?php foreach ($templates as $type => $template): ?>
                    <div class="template-card" onclick="useTemplate('<?php echo $type; ?>', '<?php echo addslashes($template); ?>')">
                        <div class="template-title"><?php echo ucfirst(str_replace('_', ' ', $type)); ?></div>
                        <div class="template-message"><?php echo htmlspecialchars($template); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Analytics Tab -->
        <div id="analytics" class="tab-content">
            <h2>📊 SMS Analytics</h2>
            <div id="analytics-content">
                <p>Loading analytics...</p>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
            
            // Load analytics if analytics tab is selected
            if (tabName === 'analytics') {
                loadAnalytics();
            }
        }

        // Character counter for SMS message
        document.getElementById('sms_message').addEventListener('input', function() {
            const remaining = 160 - this.value.length;
            const counter = document.getElementById('char_counter');
            counter.textContent = remaining + ' characters remaining';
            
            if (remaining < 20) {
                counter.className = 'char-counter danger';
            } else if (remaining < 40) {
                counter.className = 'char-counter warning';
            } else {
                counter.className = 'char-counter';
            }
        });

        function useTemplate(type, template) {
            document.getElementById('sms_message').value = template;
            document.getElementById('sms_message').dispatchEvent(new Event('input'));
            showTab('create');
        }

        function filterAlerts(status) {
            // Implement filtering logic
            window.location.href = `sms-alerts.php?filter=${status}`;
        }

        function refreshAlerts() {
            window.location.reload();
        }

        function scheduleAlert(alertId) {
            const date = prompt('Enter scheduled date (YYYY-MM-DD):');
            const time = prompt('Enter scheduled time (HH:MM):');
            
            if (date && time) {
                fetch('backend/api/sms-alerts.php?action=schedule', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        alert_id: alertId,
                        scheduled_date: date,
                        scheduled_time: time
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
            }
        }

        function cancelAlert(alertId) {
            if (confirm('Are you sure you want to cancel this scheduled alert?')) {
                fetch(`backend/api/sms-alerts.php?action=cancel&id=${alertId}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
            }
        }

        function rescheduleAlert(alertId) {
            scheduleAlert(alertId);
        }

        function viewDetails(alertId) {
            window.open(`sms-alerts.php?action=details&id=${alertId}`, '_blank');
        }

        function editAlert(alertId) {
            window.location.href = `sms-alerts.php?action=edit&id=${alertId}`;
        }

        function deleteAlert(alertId) {
            if (confirm('Are you sure you want to delete this SMS alert?')) {
                fetch(`backend/api/sms-alerts.php?action=delete&id=${alertId}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
            }
        }

        function loadAnalytics() {
            const analyticsContent = document.getElementById('analytics-content');
            analyticsContent.innerHTML = '<p>Loading analytics...</p>';
            
            fetch('backend/api/sms-alerts.php?action=analytics')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const analytics = data.data;
                        analyticsContent.innerHTML = `
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-number">${analytics.overall.total_alerts}</div>
                                    <div class="stat-label">Total Alerts</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number">${analytics.overall.total_sent}</div>
                                    <div class="stat-label">Messages Sent</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number">${analytics.overall.total_delivered}</div>
                                    <div class="stat-label">Messages Delivered</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number">${analytics.overall.avg_delivery_rate.toFixed(1)}%</div>
                                    <div class="stat-label">Avg Delivery Rate</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number">$${analytics.overall.total_cost}</div>
                                    <div class="stat-label">Total Cost</div>
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    analyticsContent.innerHTML = '<p>Error loading analytics: ' + error.message + '</p>';
                });
        }
    </script>
</body>
</html>