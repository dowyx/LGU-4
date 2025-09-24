<?php
session_start();

echo "<h2>Session Debug Information</h2>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";

echo "<h3>Session Variables:</h3>";
if (isset($_SESSION)) {
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
} else {
    echo "<p>No session variables found.</p>";
}

echo "<h3>Session Status:</h3>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . session_status() . "</p>";

echo "<h3>Required Variables Check:</h3>";
echo "<ul>";
echo "<li>user_id: " . (isset($_SESSION['user_id']) ? "SET (" . $_SESSION['user_id'] . ")" : "NOT SET") . "</li>";
echo "<li>user_name: " . (isset($_SESSION['user_name']) ? "SET (" . $_SESSION['user_name'] . ")" : "NOT SET") . "</li>";
echo "<li>user_email: " . (isset($_SESSION['user_email']) ? "SET (" . $_SESSION['user_email'] . ")" : "NOT SET") . "</li>";
echo "<li>user_role: " . (isset($_SESSION['user_role']) ? "SET (" . $_SESSION['user_role'] . ")" : "NOT SET") . "</li>";
echo "</ul>";

echo "<h3>Server Variables:</h3>";
echo "<pre>";
print_r($_SERVER);
echo "</pre>";
?>