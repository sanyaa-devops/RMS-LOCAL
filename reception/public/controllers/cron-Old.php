<?php
 // Display errors on the screen

header("Access-Control-Allow-Origin: *");  // Allow any origin (adjust if needed)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");  // Allow the necessary HTTP methods
header("Access-Control-Allow-Headers: Content-Type, Authorization");  // Allow Content-Type header and others if needed
header("Access-Control-Allow-Credentials: true");  // Include if needed for credentials

include_once 'newdb.php';

// $query = "SELECT FB.`flight_time`, GROUP_CONCAT(FB.`id`) AS flight_book_id, GROUP_CONCAT(FP.`customer_id`) AS slot_holders,GROUP_CONCAT(FB.`duration`) AS gduration, GROUP_CONCAT(SUBSTRING_INDEX(TRIM(C.customer_name), ' ', 1)) AS slot_holders_name FROM `flight_bookings` AS FB JOIN flight_purchases AS FP ON FP.id = FB.flight_purchase_id JOIN customer AS C ON C.customer_id = FP.customer_id WHERE DATE(FB.`flight_time`) = CURRENT_DATE GROUP BY FB.`flight_time` ORDER BY FB.flight_time DESC;";
$query = "SELECT FB.`flight_time`, GROUP_CONCAT(FB.`id`) AS flight_book_id, GROUP_CONCAT(FP.`customer_id`) AS slot_holders,GROUP_CONCAT(FB.`duration`) AS gduration, GROUP_CONCAT(SUBSTRING_INDEX(TRIM(C.customer_name), ' ', 1)) AS slot_holders_name FROM `flight_bookings` AS FB JOIN flight_purchases AS FP ON FP.id = FB.flight_purchase_id JOIN customer AS C ON C.customer_id = FP.customer_id LEFT JOIN flight_booking_cron fbc ON fb.id = fbc.flight_bookings_id WHERE fbc.flight_bookings_id IS NULL AND DATE(FB.`flight_time`) = CURRENT_DATE GROUP BY FB.`flight_time` ORDER BY FB.flight_time DESC;";
$result = outputs($query);

$result_array = [];
if ($result) {
    while ($row = mysqli_fetch_array($result)) {
        $crow = [];
        $crow['flight_time']        = $row['flight_time'];
        $crow['slot_holders']       = $row['slot_holders'];
        $crow['duration']           = $row['gduration'];
        $crow['slot_holders_name']  = $row['slot_holders_name'];
        $crow['flight_book_id']     = $row['flight_book_id'];
        $result_array[]         = $crow;
    }
}

$result = $values = $values_temp = [];
foreach ($result_array as $rrow) {
    // [flight_time] => 2024-10-14 13:00:00
    //         [slot_holders] => 158974,150875,147058
    //         [duration] => 15,60,15

    //id	flight_book_id	customer_id	slot_start_time	start_time	duration	

    $duration           = explode(",", $rrow['duration']);
    $flight_time        = $rrow['flight_time'];
    $slot_holders       = explode(",", $rrow['slot_holders']);
    $slot_holders_name  = explode(",", $rrow['slot_holders_name']);
    $flight_book_id     = explode(",", $rrow['flight_book_id']);
    if (array_sum($duration) <= 30) {
        $tempp = arrangeData($slot_holders, $flight_time, $flight_book_id, $duration);
    } else {
        $rsror = concorrentDurationCheck($slot_holders, $flight_book_id, $duration);
        $slot_holders = $rsror['slot_holders'];
        $flight_book_id = $rsror['flight_book_id'];
        $duration = $rsror['duration'];
        // if(!isset($flight_book_id[0])){
        //     echo "<pre>";print_r($rrow);exit;
        // }
        $tempp = arrangeData($slot_holders, $flight_time, $flight_book_id, $duration);
    }
    $values_temp = array_merge($values_temp, $tempp);
}
ksort($values_temp);
foreach ($values_temp as $slot_time => $booking) {
    if (count($booking) > 1) {
        $fresult = [];
        $fresult = sliceDuration($slot_time, $booking);
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
    }
}
insertBulk($values);

function concorrentDurationCheck($slot_holders, $flight_book_id, $duration)
{
    if (array_sum($duration) >= 30) {
        $unset_key = count($duration) - 1;
        unset($slot_holders[$unset_key]);
        unset($flight_book_id[$unset_key]);
        unset($duration[$unset_key]);
    }

    if (array_sum($duration) <= 30) {
        return [
            'slot_holders' => $slot_holders,
            'flight_book_id' => $flight_book_id,
            'duration' => $duration
        ];
    } else {
        return concorrentDurationCheck($slot_holders, $flight_book_id, $duration);
    }
}

function arrangeData($slot_holders, $flight_time, $flight_book_id, $durations)
{
    $values_temp = [];
    if (count($slot_holders) >= 1) {
        $slot_start_time = $flight_time;
        $start_time = new DateTime($flight_time);
        $start_time = $start_time->format('H:i:s');

        $slot_end_time = strtotime("+30 minutes", strtotime($start_time));
        $slot_end_time = date('H:i:s', $slot_end_time);
        $is_first = true;
        foreach ($flight_book_id as $user_row_key => $user_row_value) {
            // $flight_book_id = $flight_book_id[$user_row_key];
            $customer_id = $slot_holders[$user_row_key];
            $duration = $durations[$user_row_key];
            if ($is_first === false) {
                $start_time = $end_time;
            }
            $end_time = strtotime("+" . $duration . " minutes", strtotime($start_time));
            $end_time = date('H:i:s', $end_time);
            // $values[] = "('$user_row_value', '$customer_id', '$slot_start_time', '$start_time', '$duration', '$end_time')";
            $values_temp[$slot_start_time][] = [
                'user_row_value'    => $user_row_value,
                'customer_id'       => $customer_id,
                'slot_start_time'   => $slot_start_time,
                'start_time'        => $start_time,
                'duration'          => $duration,
                'end_time'          => $end_time
            ];
            $is_first = false;
        }
    } else {
        // if(!isset($flight_book_id[0])){
        //     echo "<pre>";print_r($slot_holders);
        //     echo "<pre>";print_r($flight_time);
        //     echo "<pre>";print_r($flight_book_id);
        //     echo "<pre>";print_r($durations);exit;
        // }

        $flight_book_id = $flight_book_id[0];
        $customer_id = $slot_holders[0];
        $slot_start_time = $flight_time;
        $duration = $durations[0];
        $start_time = new DateTime($slot_start_time);
        $start_time = $start_time->format('H:i:s');
        $end_time = strtotime("+" . $duration . " minutes", strtotime($start_time));
        $end_time = date('H:i:s', $end_time);
        // $values[] = "('$flight_book_id', '$customer_id', '$slot_start_time', '$start_time', '$duration', '$end_time')";
        $values_temp[$slot_start_time][] = [
            'user_row_value'    => $flight_book_id,
            'customer_id'       => $customer_id,
            'slot_start_time'   => $slot_start_time,
            'start_time'        => $start_time,
            'duration'          => $duration,
            'end_time'          => $end_time
        ];
    }
    return $values_temp;
}

function sliceDuration($slot_time, $bookings)
{

    $durations = array_map(function ($item) {
        return $item['duration'];
    }, $bookings);

    $booking = [];
    arsort($durations);
    foreach ($durations as $keyd => $values) {
        $booking[] = $bookings[$keyd];
    }

    $min_duration = min($durations);

    if ($min_duration > 2) {
        if (count(array_unique($durations)) === 1) {
            $min_duration = 2;
        }
    }
    $user_balance_duration = [];
    $user = [];
    $maxSlotCount = 0;
    $ai = 1;
    foreach ($booking as $val) {
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
    //Update the start and end timing and suffle the slot
    $rresult = [];
    $is_first = true;
    $ai = 1;
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

function insertBulk($values)
{
    $sql = "INSERT INTO flight_rotation (flight_book_id, customer_id, slot_start_time, start_time, duration, end_time, order_by) VALUES ";
    $sql .= implode(',', $values);
    inputs($sql);
    $sql1 = "INSERT INTO flight_booking_cron (flight_bookings_id, created_date) VALUES ";
    $now = date("Y-m-d");
    $firstElements = array_map(function ($item) use ($now) {
        $string = preg_replace("/[()]/", "", $item);
        $array = array_map('trim', explode(',', preg_replace("/'/", "", $string)));
        return "('" . $array[0] . "', '" . $now . "')";
    }, $values);
    $sql1 .= implode(',', $firstElements);
    inputs($sql1);
}

echo "Cron process completed!";
