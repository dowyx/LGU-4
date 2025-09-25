<?php
session_start();

// Handle AJAX logout request
error_log('Checking for AJAX logout request. Method: ' . $_SERVER['REQUEST_METHOD'] . ', POST data: ' . print_r($_POST, true));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'logout') {
    error_log('AJAX logout request received');
    // Clear all session data
    session_unset();
    session_destroy();
    
    // Set content type header
    header('Content-Type: application/json');
    
    // Return success response
    $response = ['success' => true, 'message' => 'Logged out successfully'];
    error_log('AJAX logout response: ' . json_encode($response));
    echo json_encode($response);
    exit();
}

// Set a test session variable
$_SESSION['test'] = 'test_value';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logout Test</title>
</head>
<body>
    <h1>Logout Test</h1>
    <p>Session test value: <?php echo isset($_SESSION['test']) ? $_SESSION['test'] : 'not set'; ?></p>
    <button id="logoutBtn">Logout</button>
    
    <script>
        document.getElementById('logoutBtn').addEventListener('click', async function(e) {
            e.preventDefault();
            console.log('Logout button clicked');
            
            try {
                const formData = new FormData();
                formData.append('action', 'logout');
                
                const response = await fetch('test_logout.php', {
                    method: 'POST',
                    body: formData
                });
                
                console.log('Response status:', response.status);
                const result = await response.json();
                console.log('Response:', result);
                
                if (result.success) {
                    console.log('Logout successful');
                    alert('Logout successful');
                } else {
                    console.error('Logout failed');
                    alert('Logout failed');
                }
            } catch (error) {
                console.error('Logout error:', error);
                alert('Logout error: ' + error.message);
            }
        });
    </script>
</body>
</html>