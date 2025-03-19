<?php
error_reporting(0); // Display errors on the screen

header("Access-Control-Allow-Origin: *");  // Allow any origin (adjust if needed)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");  // Allow the necessary HTTP methods
header("Access-Control-Allow-Headers: Content-Type, Authorization");  // Allow Content-Type header and others if needed
header("Access-Control-Allow-Credentials: true");  // Include if needed for credentials

include_once 'newdb.php';
include_once 'check_status.php';


$query = "SELECT count(*) as tunnel_hold_count  FROM flight_rotation WHERE tunnel_hold = 1  and status = 'hold' and `start_time` >= CURRENT_TIME";
$result = outputs($query);
$values  = $result->fetch_assoc();

if($values && $values['tunnel_hold_count'] > 0){
    echo json_encode(['status'=>'success','flag'=>1]);
}else{
    echo json_encode(['status'=>'success','flag'=>0]);
}
exit;

