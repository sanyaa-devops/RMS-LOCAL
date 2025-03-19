<?php
error_reporting(0); // Display errors on the screen
header("Access-Control-Allow-Origin: *");  // Allow any origin (adjust if needed)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");  // Allow the necessary HTTP methods
header("Access-Control-Allow-Headers: Content-Type, Authorization");  // Allow Content-Type header and others if needed
header("Access-Control-Allow-Credentials: true");  // Include if needed for credentials

include_once 'newdb.php';

$postData = file_get_contents('php://input');
$data = json_decode($postData, true);

$camera_name = $data['name'];
$bluetooth_name = $data['bluetoothAddress'];


$query = "INSERT INTO camera_settings (camera_name, bluetooth_name, created_at) VALUES ('$camera_name', '$bluetooth_name', now())";
$result = inputs($query);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Camera settings saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save camera settings']);
}
