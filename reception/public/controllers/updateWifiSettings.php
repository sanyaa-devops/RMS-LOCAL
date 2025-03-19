<?php
 // Display errors on the screen

header("Access-Control-Allow-Origin: *");  // Allow any origin (adjust if needed)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");  // Allow the necessary HTTP methods
header("Access-Control-Allow-Headers: Content-Type, Authorization");  // Allow Content-Type header and others if needed
header("Access-Control-Allow-Credentials: true");  // Include if needed for credentials

include_once 'newdb.php';

// Get the raw POST data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData);

$wifi_name = $data->wifi_name;
$wifi_password = $data->wifi_password;

//insert to wifi_settings table if table is empty otherwise update
$sql = "SELECT * FROM wifi_settings";
$result = outputs($sql);
if ($result) {
    $sql = "UPDATE wifi_settings SET wifi_name='$wifi_name', wifi_password='$wifi_password' ";
} else {
    $sql = "INSERT INTO wifi_settings (wifi_name, wifi_password,created_at) VALUES ('$wifi_name', '$wifi_password',now())";
}

$run = inputs($sql);
if ($run) {
    echo json_encode(array("status" => "success", "message" => "Wifi settings updated successfully"));
} else {
    echo json_encode(array("status" => "error", "message" => "Failed to update wifi settings"));
}
