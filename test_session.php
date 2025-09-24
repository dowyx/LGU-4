<?php
session_start();

echo "<h2>Session Test</h2>";

// Set some session variables
$_SESSION['test_user_id'] = 123;
$_SESSION['test_user_name'] = 'Test User';
$_SESSION['test_user_email'] = 'test@example.com';
$_SESSION['test_user_role'] = 'Tester';

echo "<p>Session variables set:</p>";
echo "<ul>";
echo "<li>test_user_id: " . $_SESSION['test_user_id'] . "</li>";
echo "<li>test_user_name: " . $_SESSION['test_user_name'] . "</li>";
echo "<li>test_user_email: " . $_SESSION['test_user_email'] . "</li>";
echo "<li>test_user_role: " . $_SESSION['test_user_role'] . "</li>";
echo "</ul>";

echo "<p><a href='test_session2.php'>Check in another script</a></p>";
?>