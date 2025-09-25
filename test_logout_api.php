<?php
// Test the logout API endpoint
$url = 'http://localhost/fck/home.php';

// Initialize cURL session
$ch = curl_init($url);

// Set cURL options
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['action' => 'logout']));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);

// Execute the request
$response = curl_exec($ch);

// Get HTTP status code
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Close cURL session
curl_close($ch);

// Output results
echo "HTTP Status Code: " . $httpCode . "\n";
echo "Response:\n" . $response . "\n";
?>