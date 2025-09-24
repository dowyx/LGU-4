<?php
session_start();

echo "<h2>Session Test - Page 2</h2>";

echo "<p>Session variables retrieved:</p>";
echo "<ul>";
echo "<li>test_user_id: " . (isset($_SESSION['test_user_id']) ? $_SESSION['test_user_id'] : 'NOT SET') . "</li>";
echo "<li>test_user_name: " . (isset($_SESSION['test_user_name']) ? $_SESSION['test_user_name'] : 'NOT SET') . "</li>";
echo "<li>test_user_email: " . (isset($_SESSION['test_user_email']) ? $_SESSION['test_user_email'] : 'NOT SET') . "</li>";
echo "<li>test_user_role: " . (isset($_SESSION['test_user_role']) ? $_SESSION['test_user_role'] : 'NOT SET') . "</li>";
echo "</ul>";

echo "<p><a href='test_session.php'>Back to first script</a></p>";
?>