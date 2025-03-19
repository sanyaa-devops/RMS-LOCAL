<?php
ini_set('max_execution_time', 180);
error_reporting(0); // Display errors on the screen

header("Access-Control-Allow-Origin: http://127.0.0.1:5000"); // Change to your frontend's origin
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // Allow methods
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Allow specific headers
header("Access-Control-Max-Age: 86400"); // Cache preflight response for 1 day

include 'newdb.php';

// Get camera_name from POST request
$postData = file_get_contents('php://input');
$data = json_decode($postData, true);

$camera_name = $data['name'];
$bluetooth_name = $data['bluetooth_name'];

$cam_count = "SELECT * FROM camera_settings WHERE status = '1'";
$cam_result = outputs($cam_count);

if (mysqli_num_rows($cam_result) == 3) {
    echo json_encode(["status" => "max", "message" => "Maximum camera limit reached"]);
    exit;
}

if ($camera_name) {
    // Check if the camera_name exists in the database
    $check_query = "SELECT * FROM camera_settings WHERE camera_name = '$camera_name'";
    $check_result = outputs($check_query); // Execute query

    if ($check_result) {
        // Camera name exists, update the status to 0
        $update_query = "UPDATE camera_settings SET status = 0 WHERE camera_name = '$camera_name'";
        $update_result = inputs($update_query);

        //for returing, then it will show newly added camera name
        $id_fetch_qry = "SELECT id FROM camera_settings WHERE camera_name = '$camera_name' ";
        $id_result = outputs($id_fetch_qry);
        $id = mysqli_fetch_all($id_result, MYSQLI_ASSOC);

        if ($update_result) { 
            echo json_encode(["status" => "success", "message" => "Camera Updated successfully", "id" => $id[0]['id']]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update camera status."]);
        }
    } else {
        // Camera name does not exist, insert a new record
        $insert_query = "INSERT INTO camera_settings (camera_name,camera_bleak_name, status) VALUES ('$camera_name','$bluetooth_name', 0)";
        $insert_result = inputs($insert_query);

        $id_fetch_qry = "SELECT id FROM camera_settings WHERE camera_name = '$camera_name' ";
        $id_result = outputs($id_fetch_qry);
        $id = mysqli_fetch_all($id_result, MYSQLI_ASSOC);

        if ($insert_result) {
            echo json_encode(["status" => "success", "message" => "Camera added successfully", "id" => $id[0]['id']]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to add camera"]);
        }
    }
} else {
    echo json_encode(["status" => "error", "message" => "camera_name not provided."]);
}
