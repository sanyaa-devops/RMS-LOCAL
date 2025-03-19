<?php
 // Display errors on the screen

header("Access-Control-Allow-Origin: *");  // Allow any origin (adjust if needed)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");  // Allow the necessary HTTP methods
header("Access-Control-Allow-Headers: Content-Type, Authorization");  // Allow Content-Type header and others if needed
header("Access-Control-Allow-Credentials: true");  // Include if needed for credentials

include_once 'newdb.php';

$query = "SELECT slot_start_time, GROUP_CONCAT(SUBSTRING_INDEX(C.customer_name, ' ', 1)) AS g_customer_name, GROUP_CONCAT(start_time) AS g_start_time, GROUP_CONCAT(duration) AS g_duration, GROUP_CONCAT(end_time) AS g_end_time FROM `flight_rotation` AS FR JOIN customer AS C ON FR.customer_id = C.customer_id GROUP BY FR.slot_start_time ORDER BY FR.id DESC;";
$result = outputs($query);
$result_array = [];
if ($result) {
    while ($row = mysqli_fetch_array($result)) {
        $crow = [];
        $crow['slot_start_time']    = $row['slot_start_time'];
        $crow['g_customer_name']    = $row['g_customer_name'];
        $crow['g_start_time']       = $row['g_start_time'];
        $crow['g_duration']         = $row['g_duration'];
        $crow['g_end_time']         = $row['g_end_time'];
        $result_array[]             = $crow;
    }

    $result = [];
    foreach ($result_array as $rrow) {
        $g_customer_name    = explode(",", $rrow['g_customer_name']);
        $g_start_time       = explode(",", $rrow['g_start_time']);
        $g_duration         = explode(",", $rrow['g_duration']);
        $g_end_time         = explode(",", $rrow['g_end_time']);
        foreach ($g_start_time as $key => $val) {
            $result[$rrow['slot_start_time']][$val]['customer_name']  = $g_customer_name[$key];
            $result[$rrow['slot_start_time']][$val]['start_time']     = $g_start_time[$key];
            $result[$rrow['slot_start_time']][$val]['duration']       = $g_duration[$key];
            $result[$rrow['slot_start_time']][$val]['end_time']       = $g_end_time[$key];
        }
    }
    success("success", $result);
}
