<?php
 // Display errors on the screen

header("Access-Control-Allow-Origin: *");  // Allow any origin (adjust if needed)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");  // Allow the necessary HTTP methods
header("Access-Control-Allow-Headers: Content-Type, Authorization");  // Allow Content-Type header and others if needed
header("Access-Control-Allow-Credentials: true");  // Include if needed for credentials

include_once 'newdb.php';
include_once 'check_status.php';

// Get the raw POST data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData);
$slot = $data->g_slot_time;
$cid = $data->g_customer_id;
$status = $data->g_status === true ? "hold" : "schedule";
$id_check = "SELECT * FROM `flight_rotation` WHERE `slot_start_time` LIKE '$slot' AND customer_id = '$cid' AND `status` != 'ongoing' and `status` != 'complete' ORDER BY order_by LIMIT 0, 1;";
$resultHold = outputs($id_check);
$status_array = ['schedule', 'ongoing', 'complete', 'hold', 'next'];
function findHoldTime($slot, $lastHoldTime){
    $time = strtotime("+1 seconds", strtotime($lastHoldTime));
    $time = date('H:i:s', $time);
    
    $currentSlot = "SELECT * FROM `flight_rotation` WHERE `slot_start_time` LIKE '$slot' AND start_time = '$time' ORDER BY order_by ASC;";
    $currentSlot = outputs($currentSlot);
    
    if($currentSlot){
        return findHoldTime($slot, $time);
    }else{
        return $time;
    }
}

if ($resultHold) {
    if (in_array($status, $status_array)) {
        $isNext = findCurrentSlotNext($slot);
        if ($status === 'schedule') {
            $resultHold = $resultHold->fetch_object();
            $id = $resultHold->id;
            $time = $resultHold->start_time;
            $time = findHoldTime($slot, $time);
            $currentSlot = "SELECT * FROM `flight_rotation` WHERE `slot_start_time` LIKE '$slot' ORDER BY order_by ASC;";
            $currentSlot = outputs($currentSlot);
            $currentSlot = $currentSlot->fetch_all(MYSQLI_ASSOC);
            foreach ($currentSlot as $val) {
                $currentSlotArray[$val['order_by']] = $val;
            }
            $updateStartSql = '';
            $updateEndSql = '';
            $uids = "";
            foreach ($currentSlotArray as $key => $val) {
                if ($resultHold->order_by < $key && isset($start_time)) {
                    $id = $val['id'];
                    $seconds = $val['duration'] * 60;
                    $end_time = strtotime("+" . $seconds . " seconds", strtotime($start_time));
                    $end_time = date('H:i:s', $end_time);
                    $updateStartSql .= "WHEN $id THEN '$start_time'";
                    $updateEndSql .= "WHEN $id THEN '$end_time'";
                    $start_time = $end_time;
                    if ($uids === '') {
                        $uids .= $id;
                    } else {
                        $uids .= ", " . $id;
                    }
                } elseif ($resultHold->order_by == $key) {
                    $start_time = $val['start_time'];
                    $start_time = strtotime("+0 seconds", strtotime($start_time));
                    $start_time = date('H:i:s', $start_time);
                }
            }
            $updateQuery = "UPDATE `flight_rotation` SET `start_time` = CASE `id` " . $updateStartSql . "ELSE `start_time` END, `end_time` = CASE `id`" . $updateEndSql . "ELSE `end_time`
    END WHERE `id` IN (" . $uids . ");";
            inputs($updateQuery);
            $query = "UPDATE `flight_rotation` SET `status` = 'hold', start_time = '" . $time . "' WHERE `id` = '$resultHold->id'";
            $result = inputs($query);
            updateNext();
        } else {
            // $currentSlotIsRunning = "SELECT * FROM `flight_rotation` WHERE `slot_start_time` LIKE '$slot' AND `status` = 'ongoing'";
            // $currentSlotIsRunning = outputs($currentSlotIsRunning);
            // var_dump($currentSlotIsRunning);
            // echo "<pre>";print_r(date("Y-m-d H:i:s"));exit; 
            // if($currentSlotIsRunning)
            // $currentSlotIsRunning = $currentSlotIsRunning->num_rows;

            // if ($currentSlotIsRunning && $currentSlotIsRunning > 0) {
                $lastCard = "SELECT * FROM `flight_rotation` WHERE `slot_start_time` LIKE '$slot' ORDER BY order_by DESC LIMIT 0, 1;";
                $lastCard = outputs($lastCard);
                $lastCard = $lastCard->fetch_object();

                $holdRow = "SELECT * FROM `flight_rotation` WHERE `slot_start_time` LIKE '$slot' AND customer_id = '$cid' AND `status` = 'hold'";
                $holdRow = outputs($holdRow);
                $holdRow = $holdRow->fetch_assoc();
                $holdRowID = $holdRow['id'];
                unset($holdRow['id']);

                $start_time = new DateTime($lastCard->end_time);
                $start_time = $start_time->format('H:i:s');

                $duration = $holdRow['duration'];
                $seconds = $duration * 60;
                $end_time = strtotime("+" . $seconds . " seconds", strtotime($start_time));
                $end_time = date('H:i:s', $end_time);

                $holdRow['start_time'] = $start_time;
                $holdRow['end_time'] = $end_time;
                $holdRow['status'] = "schedule";
                $holdRow['order_by'] = $lastCard->order_by + 1;

                $columns = implode(", ", array_keys($holdRow));
                $placeholders = implode(", ", array_map(function ($value) {
                    return "'" . $value . "'";
                }, $holdRow));
                $sql_insert = "DELETE FROM `flight_rotation` WHERE `id` = $holdRowID";
                $result = inputs($sql_insert);
                $sql_insert = "INSERT INTO `flight_rotation` ($columns) VALUES ($placeholders)";
                $result = inputs($sql_insert);
                updateNext();
            // }
        }
        if ($result) {
            $response = array(
                'status' => 'success',
                'message' => 'Status updated successfully'
            );
        }
    } else {
        $response = array(
            'status' => 'error',
            'message' => 'Invalid status'
        );
    }
} else {
    $response = array(
        'status' => 'error',
        'message' => 'Invalid ID'
    );
}
echo json_encode($response);
