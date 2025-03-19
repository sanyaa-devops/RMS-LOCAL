<?php
error_reporting(1);
date_default_timezone_set("Asia/Dubai");
function findCurrentSlotHold($slot){
    $currentSlot = "SELECT * FROM `flight_rotation` WHERE `slot_start_time` LIKE '$slot' AND `status` = 'hold' ORDER BY order_by ASC;";
    $currentSlot = outputs($currentSlot);
    if($currentSlot){
        return true;
    }else{
        return false;
    }
}


function findCurrentSlotNext($slot){
    $currentSlot = "SELECT * FROM `flight_rotation` WHERE `slot_start_time` LIKE '$slot' AND `status` = 'next' ORDER BY order_by ASC;";
    $currentSlot = outputs($currentSlot);
    if($currentSlot){
        return true;
    }else{
        return false;
    }
}

function findCurrentSlotOngoing($slot){
    $currentSlot = "SELECT * FROM `flight_rotation` WHERE `slot_start_time` LIKE '$slot' AND `status` = 'ongoing' ORDER BY order_by ASC;";
    $currentSlot = outputs($currentSlot);
    if($currentSlot){
        return true;
    }else{
        return false;
    }
}

function findNext(){
    $currentSlot = "SELECT * FROM `flight_rotation` WHERE `status` = 'next' ORDER BY order_by ASC;";
    $currentSlot = outputs($currentSlot);
    if($currentSlot){
        return true;
    }else{
        return false;
    }
}

function updateNext(){
    $isNextAvail = findNext();
    if($isNextAvail === false){
        $currentSlot = "UPDATE flight_rotation SET status = 'next' WHERE id = (SELECT id FROM (SELECT id FROM flight_rotation WHERE status = 'schedule' AND start_time >= CURRENT_TIME ORDER BY start_time ASC LIMIT 1) AS temp_table)";
        $currentSlot = inputs($currentSlot);
    }
}

function removeNext(){
    $currentSlot = "UPDATE flight_rotation SET status = 'schedule' WHERE `status` = 'next';";
    $currentSlot = inputs($currentSlot);
    updateNext();
}

function updateOverallStatus(){
    $time = date("H:i:s");
    $query = "UPDATE flight_rotation SET `status` = 'complete' WHERE `end_time` < '$time' AND status != 'hold'";
    $currentSlot = inputs($query);
    $ongoing_query = "UPDATE flight_rotation SET status = 'ongoing' WHERE start_time <= '$time' AND end_time >= '$time' AND status != 'hold'";
    $currentSlot = inputs($ongoing_query);
    $completed_query = "UPDATE flight_rotation SET status = 'schedule' WHERE start_time > '$time' AND status != 'hold'";
    $currentSlot = inputs($completed_query);
    $update_query = "UPDATE flight_rotation SET status = 'next' WHERE id = (SELECT id FROM ( SELECT id FROM flight_rotation WHERE status = 'schedule' ORDER BY start_time ASC LIMIT 1 ) AS temp_table )";
    $currentSlot = inputs($update_query);
}