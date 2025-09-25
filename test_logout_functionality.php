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

// Set a test session
$_SESSION['test_user'] = 'Test User';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Logout Functionality</title>
</head>
<body>
    <h1>Test Logout Functionality</h1>
    <p>Session status: <?php echo isset($_SESSION['test_user']) ? 'Active (' . $_SESSION['test_user'] . ')' : 'Inactive'; ?></p>
    <button id="testLogout">Test Logout</button>
    <div id="result"></div>
    
    <script>
        document.getElementById('testLogout').addEventListener('click', async function() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = 'Testing logout...';
            
            try {
                console.log('Sending logout request');
                
                // Create form data
                const formData = new FormData();
                formData.append('action', 'logout');
                
                // Send request to current page
                const response = await fetch('test_logout_functionality.php', {
                    method: 'POST',
                    body: formData
                });
                
                console.log('Response status:', response.status);
                
                // Try to get response as JSON
                const result = await response.json();
                console.log('Response:', result);
                
                resultDiv.innerHTML = `
                    <p>Status: ${response.status}</p>
                    <p>Success: ${result.success}</p>
                    <p>Message: ${result.message}</p>
                `;
                
                // Reload page to see session status
                setTimeout(() => {
                    location.reload();
                }, 2000);
                
            } catch (error) {
                console.error('Error:', error);
                resultDiv.innerHTML = `<p>Error: ${error.message}</p>`;
            }
        });
    </script>
</body>
</html>