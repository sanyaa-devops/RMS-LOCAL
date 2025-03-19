<?php
ini_set('max_execution_time', 180);
// Display errors on the screen

header("Access-Control-Allow-Origin: *"); // Change to your frontend's origin
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // Allow methods
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Allow specific headers

include 'newdb.php';


$query = "SELECT wifi_name,wifi_password FROM wifi_settings";
$result = outputs($query);
if ($result) {
    $data = mysqli_fetch_assoc($result);
    echo json_encode($data);
} else {
    echo json_encode(array("error" => "Failed to retrieve wifi settings"));
    exit;
}
