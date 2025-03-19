<?php
 // Display errors on the screen
header("Access-Control-Allow-Origin: http://127.0.0.1:5000"); // Change to your frontend's origin
header("Access-Control-Allow-Methods: GET, POST, OPTIONS"); // Allow methods
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Allow specific headers
header("Access-Control-Max-Age: 86400"); // Cache preflight response for 1 day
header("Access-Control-Allow-Origin: *");  
include_once 'newdb.php';
echo "<pre>";print_r($_POST);exit;
$query = "SELECT 
            slot_start_time, 
            GROUP_CONCAT(SUBSTRING_INDEX(C.customer_id, ' ', 1)) AS g_customer_id,
            GROUP_CONCAT(FR.id) AS card_id,
            GROUP_CONCAT(SUBSTRING_INDEX(C.customer_name, ' ', 1)) AS g_customer_name,
            GROUP_CONCAT(FR.status) AS g_status,
            GROUP_CONCAT(start_time) AS g_start_time, 
            GROUP_CONCAT(duration) AS g_duration, 
            GROUP_CONCAT(end_time) AS g_end_time,
            MAX(FR.id) AS max_id
          FROM 
            flight_rotation AS FR 
          JOIN 
            customer AS C ON FR.customer_id = C.customer_id 
          WHERE DATE(FR.slot_start_time) = CURRENT_DATE
          GROUP BY 
            FR.slot_start_time 
          ORDER BY 
            max_id DESC";
$result = outputs($query);
$result_array = [];
if ($result) {
    while ($row = mysqli_fetch_array($result)) {
        $crow = [];
        $crow['slot_start_time']    = $row['slot_start_time'];
        $crow['g_customer_id']      = $row['g_customer_id'];
        $crow['g_customer_name']    = $row['g_customer_name'];
        $crow['g_start_time']       = $row['g_start_time'];
        $crow['g_duration']         = $row['g_duration'];
        $crow['g_end_time']         = $row['g_end_time'];
        $crow['g_status']         = $row['g_status'];
        $crow['card_id']         = $row['card_id'];
        $result_array[]             = $crow;
    }

    $result = [];
    foreach($result_array as $rrow){
       $g_customer_id    = explode(",", $rrow['g_customer_id']);
        $g_customer_name    = explode(",", $rrow['g_customer_name']);
        $g_start_time       = explode(",", $rrow['g_start_time']);
        $g_duration         = explode(",", $rrow['g_duration']);
        $g_status         = explode(",", $rrow['g_status']);
        $g_card_id         = explode(",", $rrow['card_id']);
        if($rrow['g_end_time'])
            $g_end_time         = explode(",", $rrow['g_end_time']);
        foreach($g_start_time as $key => $val){
            $result[$rrow['slot_start_time']][$val]['g_customer_name']  = $g_customer_name[$key];
            $result[$rrow['slot_start_time']][$val]['g_start_time']     = $g_start_time[$key];
            $result[$rrow['slot_start_time']][$val]['g_duration']       = $g_duration[$key];
            $result[$rrow['slot_start_time']][$val]['g_status']         = $g_status[$key];
            $result[$rrow['slot_start_time']][$val]['g_card_id']        = $g_card_id[$key];
            $result[$rrow['slot_start_time']][$val]['g_end_time']       = $g_end_time[$key]??null;
            $result[$rrow['slot_start_time']][$val]['g_customer_id']    = $g_customer_id[$key]??null;
        }
    }
    
    success("success", $result);
}
