<?php
 // Display errors on the screen

header("Access-Control-Allow-Origin: *");  // Allow any origin (adjust if needed)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");  // Allow the necessary HTTP methods
header("Access-Control-Allow-Headers: Content-Type, Authorization");  // Allow Content-Type header and others if needed
header("Access-Control-Allow-Credentials: true");  // Include if needed for credentials

include_once 'newdb.php';

$query = "SELECT `status` FROM flight_rotation WHERE `status` = 'hold' and start_time >= DATE_SUB(CURRENT_TIME, INTERVAL 0 MINUTE) ";
$result = outputs($query);
if ($result) {
    //if tunnel been already holded, unhold all things based on current time
    $query = "UPDATE `flight_rotation` SET `status` = 'schedule', `tunnel_hold` = '0' WHERE end_time >= DATE_SUB(CURRENT_TIME, INTERVAL 0 MINUTE) and `status` != 'ongoing' and `tunnel_hold` = '1'";
    $result = inputs($query);
    if ($result) {
        $response = array("status" => "success", "message" => "Tunnel hold removed successfully","flag"=>2);
    } else {
        $response = array("status" => "error", "message" => "Failed to remove tunnel hold","flag"=>0);
    }
} else {
    //if nothing were tunnel holded in current time, hold all rotations
    $query = "UPDATE `flight_rotation` SET `status` = 'hold', `tunnel_hold` = '1' WHERE start_time >= DATE_SUB(CURRENT_TIME, INTERVAL 0 MINUTE) and `status` != 'ongoing' ";
    $result = inputs($query);
    if ($result) {
        $response = array("status" => "success", "message" => "Tunnel hold updated successfully","flag"=>1);
    } else {
        $response = array("status" => "error", "message" => "Failed to update tunnel hold","flag"=>0);
    }
}

echo json_encode($response);
