<?php
// Display errors on the screen
header("Access-Control-Allow-Origin: *");  // Allow any origin (adjust if needed)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");  // Allow the necessary HTTP methods
header("Access-Control-Allow-Headers: Content-Type, Authorization");  // Allow Content-Type header and others if needed
header("Access-Control-Allow-Credentials: true");  // Include if needed for credentials

include_once 'newdb.php';

$postData = file_get_contents('php://input');
$data = json_decode($postData, true);

$id = $data['id'];

$query = "UPDATE `camera_settings` SET `status` = 9 WHERE id = '$id' ";
// $query = "DELETE FROM `camera_settings` WHERE id = '$id' ";
$result = inputs($query);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Camera settings deleted successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete camera settings']);
}
