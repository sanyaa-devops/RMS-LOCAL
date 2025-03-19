<?php
error_reporting(0); // Display errors on the screen

header("Access-Control-Allow-Origin: *");  // Allow any origin (adjust if needed)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");  // Allow the necessary HTTP methods
header("Access-Control-Allow-Headers: Content-Type, Authorization");  // Allow Content-Type header and others if needed
header("Access-Control-Allow-Credentials: true");  // Include if needed for credentials

include_once 'newdb.php';
include_once 'check_status.php';

// Get the raw POST data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData);

$table_id_arr = explode(',', $data->data);

$order = 1;
foreach ($table_id_arr as $id) {
    $query = "UPDATE flight_rotation SET order_by = $order WHERE id = $id ";
    $result = inputs($query);
    $order++;
}

//get the slot time to start rescheduling
$query = "SELECT slot_start_time FROM flight_rotation WHERE id= $table_id_arr[0] limit 1";
$result = outputs($query);
$slot_start_time = mysqli_fetch_array($result)['slot_start_time'];

//format the slot time
$slot_time = new DateTime($slot_start_time);
$start_time = $slot_time->format('H:i:s'); //setting last end time as start time for next iteration

$query1 = "SELECT id,duration FROM flight_rotation WHERE slot_start_time = '$slot_start_time'  ORDER BY order_by";
$result1 = outputs($query1);

while ($row = mysqli_fetch_array($result1)) {
    $id = $row['id'];
    $duration = $row['duration'];
    $minutes = floor($duration);
    $seconds = ($duration - $minutes) * 60;
    $end_time = clone $slot_time; // Clone to avoid modifying the original
    $end_time->modify("+$minutes minutes");
    $end_time->modify("+$seconds seconds"); 
    $new_end_time = $end_time->format('H:i:s');
    //$end_time = date('H:i:s', strtotime("+$duration minutes", strtotime($slot_time->format('H:i:s'))));
   // echo $duration."\n";
    $qry = "UPDATE flight_rotation SET start_time = '$start_time', end_time = '$new_end_time' WHERE id = $id";
    inputs($qry);

    $start_time = $new_end_time; //setting last end time as start time for next iteration
    $slot_time = new DateTime($new_end_time);
}
updateOverallStatus();

echo json_encode(['status' => 'success']);
