<?php
ini_set('max_execution_time', 180);
 // Display errors on the screen

header("Access-Control-Allow-Origin: *"); // Change to your frontend's origin
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // Allow methods
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Allow specific headers

include 'newdb.php';

// Get camera_name from POST request
$postData = file_get_contents('php://input');
$data = json_decode($postData, true);

$query = "SELECT * FROM `camera_settings` WHERE id='" . $data['id'] . "' ";
$result = outputs($query);
if ($result) {
    $data = mysqli_fetch_assoc($result);
    if($data['camera_ip_address'] === null || $data['camera_ip_address'] === ""){
        $data = "Note: Camera added DB, wait for batch file if not started run addcamera batch file.";
    }else{
        $data = $data['output'];
    }

    echo json_encode($data);
} else {
    echo json_encode("No Data Found");
}
