<?php

 // Display errors on the screen
header("Access-Control-Allow-Origin: *");  // Allow any origin (adjust if needed)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");  // Allow the necessary HTTP methods
header("Access-Control-Allow-Headers: Content-Type, Authorization");  // Allow Content-Type header and others if needed
header("Access-Control-Allow-Credentials: true");  // Include if needed for credentials

include_once 'newdb.php';
$postData = file_get_contents('php://input');
$data = json_decode($postData, true);

$duration_change = [];
foreach ($data as $row) {
    $duration_change[$row['g_customer_id']] = $row['duration'];
}

$g_slot_time = $data[0]['g_slot_time'];

$query = 'SELECT customer_id, SUM(`duration`) as sum_duration, GROUP_CONCAT(DISTINCT flight_book_id) AS flight_book_ids, GROUP_CONCAT(DISTINCT slot_start_time) AS slot_start_time FROM flight_rotation WHERE slot_start_time = "' . $g_slot_time . '" AND (status LIKE "schedule" OR status LIKE "next") GROUP BY `customer_id`;';

$result = outputs($query);


$query = 'SELECT GROUP_CONCAT(DISTINCT order_by) AS order_by FROM flight_rotation WHERE slot_start_time = "' . $g_slot_time . '" AND (`status` = "ongoing" OR `status` = "complete" OR `status` = "hold") GROUP BY `slot_start_time`;';

$order_by = outputs($query);
$order_by = $order_by === false ? 0 : $order_by->fetch_row()[0];
$order_by = explode(',', $order_by);
$order_by = end($order_by);

$values_temp = [];
if ($result) {
    $i = 0;
    while ($row = mysqli_fetch_array($result)) {
        $values_temp[$i]['user_row_value'] = $row['flight_book_ids'];
        $values_temp[$i]['customer_id'] = $row['customer_id'];
        $values_temp[$i]['slot_start_time'] = $row['slot_start_time'];
        $values_temp[$i]['start_time'] = '';
        $values_temp[$i]['duration'] = $row['sum_duration'];
        $values_temp[$i]['end_time'] = '';
        ++$i;
    }
}

$fresult = [];
$fresult = sliceDuration($g_slot_time, $values_temp, $duration_change, $order_by);


$query = 'SELECT * FROM flight_rotation WHERE slot_start_time = "' . $g_slot_time . '" AND `status` = "next" ORDER BY id DESC;';
$isNext = outputs($query);
if($isNext){
    $isNext = 1;
}else{
    $isNext = 0;
}
$isFirst = true;
foreach ($fresult as $rval) {
    $user_row_value = $rval["user_row_value"];
    $customer_id = $rval["customer_id"];
    $slot_start_time = $rval["slot_start_time"];
    $start_time = $rval["start_time"];
    $duration = $rval["duration"];
    $end_time = $rval["end_time"];
    $order_by = $rval["order_by"];
    if($isFirst === true && $isNext > 0){
        $status = "next";
    }else{
        $status = "schedule";
    }
    $values[] = "('$user_row_value', '$customer_id', '$slot_start_time', '$start_time', '$duration', '$end_time', '$order_by', '$status')";
    $isFirst = false;
}
insertBulk($values, $g_slot_time);

function sliceDuration($slot_time, $bookings, $duration_change, $order_by)
{
    $user_balance_duration = [];
    $user = [];
    $maxSlotCount = 0;
    $order_by++;
    $ai = $order_by;
    if($bookings):
    foreach ($bookings as $val) {
        $min_duration = $duration_change[$val['customer_id']];
        for ($i = 0; $i < ceil($val['duration'] / $min_duration); $i++) {
            $user[$val['customer_id']][$i] = $val;
            $slot_duration = $val['duration'];
            if (!isset($user_balance_duration[$val['customer_id']])) {
                $user_balance_duration[$val['customer_id']] = $val['duration'];
                if ($min_duration < $user_balance_duration[$val['customer_id']]) {
                    $slot_duration = $min_duration;
                } else {
                    $slot_duration = $user_balance_duration[$val['customer_id']];
                }
            } else {
                $user_balance_duration[$val['customer_id']] = $user_balance_duration[$val['customer_id']] - $min_duration;
                if ($min_duration < $user_balance_duration[$val['customer_id']]) {
                    $slot_duration = $min_duration;
                } else {
                    $slot_duration = $user_balance_duration[$val['customer_id']];
                }
            }
            $user[$val['customer_id']][$i]['duration'] = $slot_duration;
            $user[$val['customer_id']][$i]['order_by'] = $ai;
            ++$ai;
        }
        $slotCount = count($user[$val['customer_id']]);
        if ($slotCount > $maxSlotCount) {
            $maxSlotCount = $slotCount;
        }
    }
endif;
    //Update the start and end timing and suffle the slot
    $query = 'SELECT * FROM flight_rotation WHERE slot_start_time = "' . $slot_time . '" AND (`status` = "ongoing" OR `status` = "complete" ) ORDER BY id DESC;';
    $findendtime = outputs($query);
	if($findendtime)
    $findendtime = $findendtime->fetch_array(MYSQLI_ASSOC);
    $rresult = [];
    $is_first = true;
    $ai = $order_by;
    uasort($user, function($a, $b) {
        return count($b) <=> count($a);
    });
    for ($i = 0; $i < $maxSlotCount; $i++) {
        foreach ($user as $user_row) {
            if (isset($user_row[$i])) {
                $temp_row = $user_row[$i];
                if ($is_first === false) {
                    $start_time = $end_time;
                } else {
                    $start_time = isset($findendtime['end_time']) ? strtotime("+0 seconds", strtotime($findendtime['end_time'])) : strtotime("+0 seconds", strtotime($temp_row['slot_start_time']));
                    $start_time = date('H:i:s', $start_time);
                }
                $duration = $temp_row['duration'];
                $seconds = $duration * 60;
                $end_time = strtotime("+" . $seconds . " seconds", strtotime($start_time));
                $end_time = date('H:i:s', $end_time);
                $temp_row['start_time'] = $start_time;
                $temp_row['end_time']   = $end_time;
                $temp_row['order_by']   = $ai;
                $rresult[] = $temp_row;
                $is_first = false;
                ++$ai;
            }
        }
    }

    return $rresult;
}

// insertBulk($values);

function insertBulk($values, $slot)
{
    $sql = 'DELETE FROM `flight_rotation` WHERE slot_start_time = "' . $slot . '" AND (status IN("schedule", "next"))';
    inputs($sql);
    $sql = "INSERT INTO flight_rotation (flight_book_id, customer_id, slot_start_time, start_time, duration, end_time, order_by, `status`) VALUES ";
    $sql .= implode(',', $values);
    inputs($sql);
}

echo json_encode(array("status" => "success"));
