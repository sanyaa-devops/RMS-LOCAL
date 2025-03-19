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

$customer_id = $data->g_customer_id;
$slot_time = $data->g_slot_time;

$query = "DELETE FROM `flight_rotation` WHERE `customer_id` = '$customer_id' and `slot_start_time` = '$slot_time' and `status` IN ('schedule','next') ";
$result = inputs($query);
if ($result) {
    $response = array(
        'status' => 'success',
        'message' => 'Rotation deleted successfully'
    );
} else {
    $response = array(
        'status' => 'error',
        'message' => 'Error deleting rotation'
    );
}

$get_cus_id_qry = "SELECT customer_id,duration FROM flight_rotation WHERE slot_start_time = '$slot_time' and `status` IN ('schedule','next') GROUP BY customer_id";
$all_customer = outputs($get_cus_id_qry);
if($all_customer){
while ($row = mysqli_fetch_array($all_customer)) {
    $duration_change[$row['customer_id']] = $row['duration'];
}

$g_slot_time = $slot_time;
$query = 'SELECT customer_id, SUM(`duration`) as sum_duration, GROUP_CONCAT(DISTINCT flight_book_id) AS flight_book_ids, GROUP_CONCAT(DISTINCT slot_start_time) AS slot_start_time FROM flight_rotation WHERE slot_start_time = "' . $g_slot_time . '" AND status LIKE "schedule" GROUP BY `customer_id`;';

$result = outputs($query);
$query = 'SELECT GROUP_CONCAT(DISTINCT order_by) AS order_by FROM flight_rotation WHERE slot_start_time = "' . $g_slot_time . '" AND (`status` = "ongoing" OR `status` = "complete") GROUP BY `slot_start_time`;';
$order_by = outputs($query);
$order_by = $order_by === false ? 0 : $order_by->fetch_row()[0];
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

foreach ($fresult as $rval) {
    $user_row_value = $rval["user_row_value"];
    $customer_id = $rval["customer_id"];
    $slot_start_time = $rval["slot_start_time"];
    $start_time = $rval["start_time"];
    $duration = $rval["duration"];
    $end_time = $rval["end_time"];
    $order_by = $rval["order_by"];
    $values[] = "('$user_row_value', '$customer_id', '$slot_start_time', '$start_time', '$duration', '$end_time', '$order_by')";
}
$query = "DELETE FROM `flight_rotation` WHERE `slot_start_time` = '$slot_time' and `status` IN ('schedule','next') ";
$resultf = inputs($query);
insertBulk($values, $g_slot_time);
updateNext();
}

function sliceDuration($slot_time, $bookings, $duration_change, $order_by)
{

    $user_balance_duration = [];
    $user = [];
    $maxSlotCount = 0;
    $order_by++;
    $ai = $order_by;
    foreach ($bookings as $val) {
        $min_duration = $duration_change[$val['customer_id']];
        $cardCount = ceil($val['duration'] / $min_duration);
        $cardCount = $cardCount < 1 ? 1 : $cardCount;

        for ($i = 0; $i < $cardCount; $i++) {
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
    
    //Update the start and end timing and suffle the slot
    $rresult = [];
    $is_first = true;
    $ai = $order_by;
    for ($i = 0; $i < $maxSlotCount; $i++) {
        foreach ($user as $user_row) {
            if (isset($user_row[$i])) {
                $temp_row = $user_row[$i];
                if ($is_first === false) {
                    $start_time = $end_time;
                } else {
                    $start_time = new DateTime($temp_row['slot_start_time']);
                    $start_time = $start_time->format('H:i:s');
                }
                $duration = $temp_row['duration'];
                $end_time = strtotime("+" . $duration . " minutes", strtotime($start_time));
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
    $sql = "INSERT INTO flight_rotation (flight_book_id, customer_id, slot_start_time, start_time, duration, end_time, order_by) VALUES ";
    $sql .= implode(',', $values);
    inputs($sql);
}

echo json_encode($response);
