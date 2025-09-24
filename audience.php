<?php
/**
 * Audience Segmentation Management - PHP Interface
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
        
        if (isset($_POST['create_contact'])) {
            // Create new contact
            $stmt = $db->prepare("
                INSERT INTO contacts (first_name, last_name, email, phone, address, city, state, zip_code, age_group, gender, occupation, language_preference, status, source) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 'manual')
            ");
            
            $stmt->execute([
                $_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone'],
                $_POST['address'], $_POST['city'], $_POST['state'], $_POST['zip_code'],
                $_POST['age_group'], $_POST['gender'], $_POST['occupation'], $_POST['language_preference']
            ]);
            
            $message = "Contact created successfully!";
            
        } elseif (isset($_POST['create_segment'])) {
            // Create new segment
            $stmt = $db->prepare("
                INSERT INTO audience_segments (name, description, segment_type, status, created_by) 
                VALUES (?, ?, ?, 'active', ?)
            ");
            
            $stmt->execute([
                $_POST['name'], $_POST['description'], $_POST['segment_type'], $_SESSION['user_id']
            ]);
            
            $message = "Audience segment created successfully!";
        }
        
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch data for display
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get contacts
    $contactsStmt = $db->query("
        SELECT c.*, 
               (SELECT GROUP_CONCAT(as1.name) FROM segment_contacts sc 
                JOIN audience_segments as1 ON sc.segment_id = as1.id 
                WHERE sc.contact_id = c.id) as segments
        FROM contacts c 
        WHERE c.status = 'active'
        ORDER BY c.created_at DESC 
        LIMIT 50
    ");
    $contacts = $contactsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get segments
    $segmentsStmt = $db->query("
        SELECT s.*, 
               (SELECT COUNT(*) FROM segment_contacts WHERE segment_id = s.id) as contact_count,
               u.username as created_by_name
        FROM audience_segments s
        LEFT JOIN users u ON s.created_by = u.id
        ORDER BY s.created_at DESC
    ");
    $segments = $segmentsStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $contacts = [];
    $segments = [];
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audience Management - Public Safety Campaign</title>
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
            background: #3498db;
            color: white;
        }
        .tab:hover {
            background: rgba(52, 152, 219, 0.1);
        }
        .tab.active:hover {
            background: #2980b9;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
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
        
        .btn:hover {
            opacity: 0.8;
            transform: translateY(-2px);
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        .data-table th {
            background: #f8f9fa;
            font-weight: bold;
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
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 600px;
            max-height: 80%;
            overflow-y: auto;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: black;
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
        <h1>👥 Audience Segmentation & Personal Outreach</h1>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab active" onclick="showTab('contacts')">📋 Contacts (<?php echo count($contacts); ?>)</button>
            <button class="tab" onclick="showTab('segments')">📊 Segments (<?php echo count($segments); ?>)</button>
            <button class="tab" onclick="showTab('analytics')">📈 Analytics</button>
        </div>

        <!-- Contacts Tab -->
        <div id="contacts" class="tab-content active">
            <div class="btn-group">
                <button onclick="showModal('createContactModal')" class="btn btn-success">➕ Add Contact</button>
                <button onclick="showImportModal()" class="btn btn-primary">📤 Import Contacts</button>
                <button onclick="exportContacts()" class="btn btn-primary">📥 Export Contacts</button>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>City</th>
                        <th>Age Group</th>
                        <th>Segments</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($contacts as $contact): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($contact['email'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($contact['phone'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($contact['city'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($contact['age_group'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($contact['segments'] ?? 'None'); ?></td>
                        <td>
                            <button onclick="editContact(<?php echo $contact['id']; ?>)" class="btn btn-primary">Edit</button>
                            <button onclick="deleteContact(<?php echo $contact['id']; ?>)" class="btn btn-danger">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Segments Tab -->
        <div id="segments" class="tab-content">
            <div class="btn-group">
                <button onclick="showModal('createSegmentModal')" class="btn btn-success">➕ Create Segment</button>
                <button onclick="analyzeSegments()" class="btn btn-primary">📊 Analyze All Segments</button>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Segment Name</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Contacts</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($segments as $segment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($segment['name']); ?></td>
                        <td><?php echo htmlspecialchars($segment['description'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($segment['segment_type']); ?></td>
                        <td><?php echo $segment['contact_count']; ?></td>
                        <td><?php echo htmlspecialchars($segment['created_by_name'] ?? ''); ?></td>
                        <td>
                            <button onclick="viewSegment(<?php echo $segment['id']; ?>)" class="btn btn-primary">View</button>
                            <button onclick="editSegment(<?php echo $segment['id']; ?>)" class="btn btn-primary">Edit</button>
                            <button onclick="deleteSegment(<?php echo $segment['id']; ?>)" class="btn btn-danger">Delete</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Analytics Tab -->
        <div id="analytics" class="tab-content">
            <h2>📈 Audience Analytics</h2>
            <div id="analytics-content">
                <p>Loading analytics...</p>
            </div>
        </div>
    </div>

    <!-- Create Contact Modal -->
    <div id="createContactModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('createContactModal')">&times;</span>
            <h3>Add New Contact</h3>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone">
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="address">
                    </div>
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city">
                    </div>
                    <div class="form-group">
                        <label>State</label>
                        <input type="text" name="state">
                    </div>
                    <div class="form-group">
                        <label>Zip Code</label>
                        <input type="text" name="zip_code">
                    </div>
                    <div class="form-group">
                        <label>Age Group</label>
                        <select name="age_group">
                            <option value="">Select Age Group</option>
                            <option value="18-25">18-25</option>
                            <option value="26-35">26-35</option>
                            <option value="36-45">36-45</option>
                            <option value="46-55">46-55</option>
                            <option value="56-65">56-65</option>
                            <option value="65+">65+</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                            <option value="prefer_not_to_say">Prefer not to say</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Occupation</label>
                        <input type="text" name="occupation">
                    </div>
                    <div class="form-group">
                        <label>Language Preference</label>
                        <select name="language_preference">
                            <option value="english">English</option>
                            <option value="spanish">Spanish</option>
                            <option value="french">French</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="create_contact" class="btn btn-success">Create Contact</button>
            </form>
        </div>
    </div>

    <!-- Create Segment Modal -->
    <div id="createSegmentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('createSegmentModal')">&times;</span>
            <h3>Create New Segment</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Segment Name *</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Segment Type</label>
                    <select name="segment_type">
                        <option value="manual">Manual</option>
                        <option value="dynamic">Dynamic</option>
                        <option value="imported">Imported</option>
                    </select>
                </div>
                <button type="submit" name="create_segment" class="btn btn-success">Create Segment</button>
            </form>
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

        function showModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function editContact(contactId) {
            // Implement edit contact functionality
            alert('Edit contact functionality would be implemented here for contact ID: ' + contactId);
        }

        function deleteContact(contactId) {
            if (confirm('Are you sure you want to delete this contact?')) {
                // Implement delete contact functionality via AJAX
                fetch(`backend/api/audience-segmentation.php?action=delete_contact&id=${contactId}`, {
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

        function viewSegment(segmentId) {
            // Implement view segment functionality
            window.location.href = `audience.php?action=view_segment&id=${segmentId}`;
        }

        function editSegment(segmentId) {
            // Implement edit segment functionality
            alert('Edit segment functionality would be implemented here for segment ID: ' + segmentId);
        }

        function deleteSegment(segmentId) {
            if (confirm('Are you sure you want to delete this segment?')) {
                fetch(`backend/api/audience-segmentation.php?action=delete_segment&id=${segmentId}`, {
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
            
            fetch('backend/api/audience-segmentation.php?action=segment_analytics&segment_id=1')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        analyticsContent.innerHTML = `
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-number">${data.data.metrics.total_contacts}</div>
                                    <div class="stat-label">Total Contacts</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number">${data.data.metrics.active_contacts}</div>
                                    <div class="stat-label">Active Contacts</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number">${data.data.metrics.contacts_with_phone}</div>
                                    <div class="stat-label">With Phone Numbers</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-number">${data.data.metrics.contacts_with_email}</div>
                                    <div class="stat-label">With Email Addresses</div>
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    analyticsContent.innerHTML = '<p>Error loading analytics: ' + error.message + '</p>';
                });
        }

        function showImportModal() {
            alert('Import contacts functionality would open a file upload modal here.');
        }

        function exportContacts() {
            window.open('backend/api/audience-segmentation.php?action=export&format=csv', '_blank');
        }

        function analyzeSegments() {
            alert('Segment analysis functionality would display detailed analytics for all segments.');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>