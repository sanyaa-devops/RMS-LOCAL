<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

include_once 'newdb.php';
include_once 'check_status.php';

$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);
if(!isset($data['timeslot']) || $data['timeslot'] == ""){
    $response = array(
        'status' => 'error',
        'message' => 'Invalid time slot'
    );
    echo json_encode($response);exit();
}elseif(!isset($data['interval']) || $data['interval'] == ""){
    $response = array(
        'status' => 'error',
        'message' => 'Invalid interval'
    );
    echo json_encode($response);exit();
}

$name = trim($data['name']);
$timeslot = trim($data['timeslot']);
$interval = trim($data['interval']);

$query = "SELECT sum(duration) as total_duration, COUNT(duration) as card FROM `flight_rotation` WHERE slot_start_time = '$timeslot';";
$result = outputs($query);

if($result){
    $texsistingDuration = $result->fetch_array(MYSQLI_ASSOC);
    $total_duration = $texsistingDuration['total_duration'];
    if($total_duration >= 30){
        $response = array(
            'status' => 'error',
            'message' => 'Not available sufficient duration.'
        );
        echo json_encode($response);exit();
    }
    $texsistingDuration['free_duration'] = 30 - $total_duration;
}

if($texsistingDuration['free_duration'] < $interval){
    $response = array(
        'status' => 'error',
        'message' => 'Interval duration is wrong!'
    );
    echo json_encode($response);exit();
}

if(($texsistingDuration['free_duration'] / ($texsistingDuration['card'] - 1)) < $interval){
    $response = array(
        'status' => 'error',
        'message' => 'Interval duration is wrong!'
    );
    echo json_encode($response);exit();
}

$query = "SELECT * FROM `flight_rotation` WHERE slot_start_time = '$timeslot' AND status = 'schedule' ORDER BY order_by ASC;";
$card_result = outputs($query);
if($card_result){
    $card_result = $card_result->fetch_all(MYSQLI_ASSOC);
    static $lastEndTime = "";
    static $querys = [];
    foreach($card_result as $key => $val){
        
        if ($lastEndTime == "") {
            $start_time = date('H:i:s', strtotime($val['slot_start_time']));
            $end_time = date('H:i:s', strtotime("+".($val['duration'])." minutes", strtotime($start_time)));
            $lastEndTime = $end_time;
        } else {
            $start_time = date('H:i:s', strtotime("+".($interval)." minutes", strtotime($lastEndTime)));
            $end_time = date('H:i:s', strtotime("+".($val['duration'])." minutes", strtotime($start_time)));
            $lastEndTime = $end_time;
        }
        
        $query = "UPDATE `flight_rotation` SET `start_time`='".$start_time."', `end_time`='".$end_time."' WHERE id = '".$val['id']."';";
        inputs($query);
    }
    $response = array(
        'status' => 'success',
        'message' => 'Interval added successfully!'
    );
    echo json_encode($response);exit();
}else{
    $response = array(
        'status' => 'error',
        'message' => 'Interval duration is wrong!'
    );
    echo json_encode($response);exit();
}

