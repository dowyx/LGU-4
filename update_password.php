<?php
require_once 'backend/config/database.php';
require_once 'backend/utils/helpers.php';

$database = new Database();
$conn = $database->getConnection();

// Update demo user's password
$email = 'demo@safetycampaign.org';
$new_password = 'admin123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    $query = "UPDATE users SET password = ? WHERE email = ?";
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([$hashed_password, $email]);
    
    if ($result) {
        echo "Password updated successfully for $email<br>";
        echo "New hash: $hashed_password";
    } else {
        echo "Failed to update password";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Show current user data
$query = "SELECT id, email, password FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<pre>Current user data: ";
print_r($user);
echo "</pre>";
?>
