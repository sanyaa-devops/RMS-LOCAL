<?php
 // Display errors on the screen
header("Access-Control-Allow-Origin: *");  // Allow any origin (adjust if needed)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");  // Allow the necessary HTTP methods
header("Access-Control-Allow-Headers: Content-Type, Authorization");  // Allow Content-Type header and others if needed
header("Access-Control-Allow-Credentials: true");  // Include if needed for credentials

include_once 'newdb.php';


$query = "SELECT id,camera_name,camera_ip_address,status FROM `camera_settings` WHERE `status` != 0 ";
$result = outputs($query);

if ($result) {
    $i = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $data[$i]['id'] = $row['id'];
        $data[$i]['name'] = $row['camera_name'];
        $data[$i]['ip_addess'] = $row['camera_ip_address'];
        $data[$i]['status'] = $row['status'];
        $i++;
    }
    $response = $data;
} else {
    $response = array(
        'message' => 'Failed to fetch data'
    );
}

echo json_encode($response);
