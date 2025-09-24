<?php
// Start session
session_start();

// Check if session is working
if (!isset($_SESSION['test'])) {
    $_SESSION['test'] = time();
    echo "Session test value set to: " . $_SESSION['test'] . "<br>";
} else {
    echo "Session test value is: " . $_SESSION['test'] . "<br>";
}

// Display session information
echo "<h2>Session Information</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Name: " . session_name() . "<br>";
echo "Session Save Path: " . session_save_path() . "<br>";
echo "Session Status: " . session_status() . " (2 = PHP_SESSION_ACTIVE)<br>";

// Display all session variables
echo "<h2>Session Variables</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Display PHP info for session settings
echo "<h2>PHP Session Configuration</h2>";
ob_start();
phpinfo(INFO_VARIABLES);
$phpinfo = ob_get_clean();
$phpinfo = preg_replace('%^.*<h1 class="p">PHP Variables</h1>.*?(<h2>.*?</h2>).*?(<table.*?</table>).*?<h2>%s', '$1$2', $phpinfo);
echo $phpinfo;
?>
