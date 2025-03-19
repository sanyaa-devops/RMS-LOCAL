<?php
 // Display errors on the screen
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
header("Access-Control-Allow-Origin: *");  // Allow any origin (adjust if needed)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");  // Allow the necessary HTTP methods
header("Access-Control-Allow-Headers: Content-Type, Authorization");  // Allow Content-Type header and others if needed
header("Access-Control-Allow-Credentials: true");  // Include if needed for credentials

include_once 'newdb.php';
// include_once 'newdb_two.php';
include_once 'check_status.php';

synchFromSource();

$yesterday = date("Y-m-d", strtotime("-1 day", strtotime(date("Y-m-d"))));
$yesterdayQuery = "SELECT count(id) as count_time FROM `flight_rotation` WHERE date(slot_start_time) <= '$yesterday'";
$yesterdayQuery = outputs($yesterdayQuery);
if($yesterdayQuery){
    $yesterdayQuery = $yesterdayQuery->fetch_row();
    if(isset($yesterdayQuery[0]) && $yesterdayQuery[0] > 0){
        inputs("DELETE FROM `flight_rotation` WHERE date(slot_start_time) <= '$yesterday'");
        inputs("DELETE FROM `flight_booking_cron` WHERE date(schedule_time) <= '$yesterday'");
    }
    // else{
    //     inputs("DELETE FROM `flight_booking_cron` WHERE date(schedule_time) <= '$yesterday'");
    // }
    $cCron = "select count(id) FROM `flight_rotation`;";
    $cCron = outputs($cCron);
    
    if($cCron){
        $cCronCount = $cCron->fetch_row();
        if(isset($cCronCount[0]) && $cCronCount[0] == 0)
        inputs("DELETE FROM `flight_booking_cron`;");
    }
    
    unset($yesterdayQuery);
    $yesterdayQuery = "SELECT count(id) as count_time FROM `flight_rotation` WHERE date(slot_start_time) <= '$yesterday'";
    $yesterdayQuery = outputs($yesterdayQuery);
    if($yesterdayQuery){
        $isEmpty = false;
    }else{
        $isEmpty = true;
    }
}else{
    inputs("DELETE FROM `flight_booking_cron` WHERE date(schedule_time) <= '$yesterday'");
}

mainFunc();
// if(isset($isEmpty) && $isEmpty === true){
//     mainFunc();
// }

function synchFromSource() {
    $sourceData = callCronData();
    
    usleep(1000);
    if($sourceData){
        $sqlTarget = "SELECT FB.id as fb_id, FB.flight_purchase_id as fb_flight_purchase_id, FB.from_flight_purchase_id as fb_from_flight_purchase_id, FB.flight_time as fb_flight_time, FB.duration as fb_duration, FB.created as fb_created, FP.id AS fp_id, FP.invoice_id AS fp_invoice_id, FP.flight_offer_id AS fp_flight_offer_id, FP.customer_id AS fp_customer_id, FP.vat_code_id AS fp_vat_code_id, FP.status AS fp_status, FP.deduct_from_balance AS fp_deduct_from_balance, FP.class_people AS fp_class_people, FP.discount AS fp_discount, FP.discount_id AS fp_discount_id, FP.price AS fp_price, FP.groupon_code AS fp_groupon_code, FP.created AS fp_created, C.customer_id, C.customer_name FROM `flight_bookings` AS FB JOIN flight_purchases AS FP ON FB.flight_purchase_id = FP.id JOIN customer AS C ON C.customer_id = FP.customer_id WHERE date(FB.flight_time) = CURRENT_DATE;";
        $targetToday = outputs($sqlTarget);
        $synch = false;
        if($targetToday){
            $targetData = getArray($targetToday);
            if(count($sourceData) <> count($targetData)){
                $synch = true;
            }
        }else{
            $synch = true;
        }
        
        if($synch === true){
            $findOldSql = "SELECT * FROM `flight_bookings` AS FB WHERE date(FB.flight_time) < CURRENT_DATE;";
            $findOldData = outputs($findOldSql);
            if($findOldData){
                $findOldData = getArray($findOldData);
                if($findOldData>0){
                    $synch = true;
                }
            }
        }
        
        if($synch){
            $truncateSql = "TRUNCATE `flight_bookings`;";
            inputs($truncateSql);
            $truncateSql = "TRUNCATE `flight_purchases`;";
            inputs($truncateSql);
            $fbArrayTable = [];
            $fpArrayTable = [];
            $todayCustomerDetails = [];
            foreach($sourceData as $row){
                $fbArray = [];
                $fpArray = [];
                foreach ($row as $key => $value) {
                    if (strpos($key, 'fb_') === 0) {
                        $key = str_replace("fb_","", $key);
                        $fbArray[$key] = $value;  // Add to fbArray if key starts with 'fb_'
                    } elseif (strpos($key, 'fp_') === 0) {
                        $key = str_replace("fp_","", $key);
                        $fpArray[$key] = $value;  // Add to fpArray if key starts with 'fp_'
                    }
                }
                $todayCustomerDetails[$row['customer_id']] = $row['customer_name'];
                $fbArrayTable[]=$fbArray;
                $fpArrayTable[]=$fpArray;
            }
            
            if(count($todayCustomerDetails) > 0){
                $searchCreteria = implode(",", array_keys($todayCustomerDetails));
                $todayCustomerDetailsKey = array_keys($todayCustomerDetails);
                $matchDesgination = "SELECT customer_id FROM `customer` WHERE `customer_id` IN (".$searchCreteria.");";
                $matchDesgination = outputs($matchDesgination);
                if($matchDesgination && $matchDesgination->num_rows > 0){
                    $matchDesgination = getArray($matchDesgination);
                    $matchDesgination = array_column($matchDesgination, 'customer_id');
                }else{
                    $matchDesgination = [];
                }
                    
                $missingCustomer = array_diff($todayCustomerDetailsKey, $matchDesgination);

                if(count($missingCustomer)>0){
                    $missingCustomerDetails = [];
                    foreach ($missingCustomer as $key => $value) {
                        $missingCustomerDetails[] = ['customer_id'=>$value, 'customer_name'=>$todayCustomerDetails[$value]];
                    }
                    
                    insertMultipleRecords('customer', $missingCustomerDetails);
                    
                }
                
            }
            insertMultipleRecords('flight_bookings', $fbArrayTable);
            insertMultipleRecords('flight_purchases', $fpArrayTable);

        }
    }
	return deleteRemovedBooking();
}

function deleteRemovedBooking() {
    $isUpdate = false;
    $sqlQuery = "SELECT DISTINCT FR.flight_book_id as frid FROM `flight_rotation` AS FR LEFT JOIN flight_bookings AS FB ON FB.id = FR.flight_book_id WHERE FB.id IS NULL";
    $data = outputs($sqlQuery);
    if($data && $data->num_rows > 0){
        $isUpdate = true;
        $truncateSql = "DELETE FROM `flight_rotation` WHERE flight_book_id IN (SELECT DISTINCT FR.flight_book_id as frid FROM `flight_rotation` AS FR LEFT JOIN flight_bookings AS FB ON FB.id = FR.flight_book_id WHERE FB.id IS NULL);";
        inputs($truncateSql);
        reArrange($data->fetch_all(MYSQLI_ASSOC));
    }
    $sqlQuery = "SELECT DISTINCT FBC.flight_bookings_id as frid FROM `flight_booking_cron` AS FBC LEFT JOIN flight_bookings AS FB ON FB.id = FBC.flight_bookings_id WHERE FB.id IS NULL;";
    $data2 = outputs($sqlQuery);
    if($data2 && $data2->num_rows > 0){
        $truncateSql = "DELETE FROM `flight_booking_cron` WHERE flight_bookings_id IN (SELECT DISTINCT FBC.flight_bookings_id as frid FROM `flight_booking_cron` AS FBC LEFT JOIN flight_bookings AS FB ON FB.id = FBC.flight_bookings_id WHERE FB.id IS NULL);";
        inputs($truncateSql);
    }
}

function callCronData(){
    $url = "https://store.inflightdubai.com/inflight/main/api.php";
    $postData = [
        'call' => 'getFlightBookingsCron',
        'flight_date' => date("Y-m-d"),
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Accept: application/json, text/javascript, */*; q=0.01",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36",
        "X-Requested-With: XMLHttpRequest",
        "Content-Type: application/x-www-form-urlencoded; charset=UTF-8"
    ]);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'cURL Error: ' . curl_error($ch);
    }
    curl_close($ch);
    $response = json_decode($response, true);
	//echo "<pre>";print_r($response);die;
    if(isset($response['success']) && $response['success'] == true){
        return $response['data'];
    }else{
        return [];
    }
	

}

function mainFunc(){
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
// echo "<pre>";print_r($result_array);exit;
$query = "SELECT `schedule_time`, (30 - SUM(duration)) as duration FROM `flight_booking_cron` WHERE DATE(`schedule_time`) = CURRENT_DATE GROUP BY `schedule_time` ORDER BY `flight_booking_cron`.`schedule_time` ASC;";
$result = outputs($query);

$available_time = [];
if ($result) {
    while ($row = mysqli_fetch_array($result)) {
        $available_time[$row['schedule_time']] = $row['duration'];
    }
}
// echo "<pre>";print_r($result_array);exit;
$result = $values = $values_temp = [];
foreach ($result_array as $rrow) {
    $duration           = explode(",", $rrow['duration']);
    $flight_time        = $rrow['flight_time'];
    $slot_holders       = explode(",", $rrow['slot_holders']);
    $slot_holders_name  = explode(",", $rrow['slot_holders_name']);
    $flight_book_id     = explode(",", $rrow['flight_book_id']);
    
    if (array_sum($duration) <= 30) {
        
        if (isset($available_time[$flight_time]) && $available_time[$flight_time] > array_sum($duration)) { // for check the time availability
            continue;
        }
        
        $tempp = arrangeData($slot_holders, $flight_time, $flight_book_id, $duration, $available_time);
    } else {
        $rsror = concorrentDurationCheck($slot_holders, $flight_book_id, $duration);

        $slot_holders = $rsror['slot_holders'];
        $flight_book_id = $rsror['flight_book_id'];
        $duration = $rsror['duration'];
        if (isset($available_time[$flight_time]) && $available_time[$flight_time] < array_sum($duration)) { // for check the time availability
            continue;
        }
        $tempp = arrangeData($slot_holders, $flight_time, $flight_book_id, $duration, $available_time);
    }
    
    $values_temp = array_merge($values_temp, $tempp);
}
ksort($values_temp);
$values_temp = merageSameCustomer($values_temp);

foreach ($values_temp as $slot_time => $booking) {
    if (count($booking) > 0) {
        $fresult = [];
        if ($booking[0]['user_row_value'] == "") {
            continue;
        }
        $fresult = sliceDuration($slot_time, $booking);
        foreach ($fresult as $rval) {
            createDirectory($slot_time, $rval);
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
updateOverallStatus();

$query = "SELECT MIN(start_time) as min_time, MAX(end_time) as max_time FROM `flight_rotation` WHERE DATE(slot_start_time) = CURRENT_DATE ORDER BY `flight_rotation`.`slot_start_time` ASC;";
$minMax = outputs($query);

if($minMax){
    $minMax = $minMax->fetch_object();
    echo json_encode(['status'=> "success", 'min' => $minMax->min_time, 'max'=>$minMax->max_time]);
}else{
    echo json_encode(['status'=> "failed", 'min' => "", 'max'=>""]);
}

// Unset all variables
foreach ($GLOBALS as $key => $value) {
    if ($key !== 'GLOBALS' && $key !== '_GET' && $key !== '_POST' && $key !== '_COOKIE' && $key !== '_SESSION' && $key !== '_REQUEST' && $key !== '_FILES' && $key !== 'GLOBALS') {
        unset($GLOBALS[$key]);
    }
}
}

function merageSameCustomer($data){
    $mergedData = [];

    foreach ($data as $timestamp => $entries) {
        $temp = [];
        foreach ($entries as $entry) {
            $customerId = $entry['customer_id'];
            if (isset($temp[$customerId])) {
                // Merge the durations and adjust the end time
                $temp[$customerId]['duration'] += $entry['duration'];
                $temp[$customerId]['end_time'] = $entry['end_time'];
            } else {
                $temp[$customerId] = $entry;
            }
        }
        $mergedData[$timestamp] = array_values($temp);
    }
    return $mergedData;
}

function createDirectory($slot_time, $booking)
{
    $customer_id = $booking['customer_id'];
    $dirDate = date("d-m-Y", strtotime($slot_time));
    $path = 'inflightdubai/templates/assets/videos/' . $customer_id. "/" . $dirDate;

    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
}

function concorrentDurationCheck($slot_holders, $flight_book_id, $duration)
{
    asort($duration);
    if (array_sum($duration) >= 30) {
        $unset_key = array_key_last($duration);
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

function arrangeData($slot_holders, $flight_time, $flight_book_id, $durations, $available_time)
{
    $values_temp = [];
    if (count($slot_holders) >= 1) {
        $slot_start_time = $flight_time;
        $start_time = new DateTime($flight_time);
        $start_time = $start_time->format('H:i:s');
        if (isset($available_time[$flight_time])){
            $query = 'SELECT end_time FROM flight_rotation WHERE slot_start_time = "' . $flight_time . '" ORDER BY id DESC LIMIT 0, 1;';
            $findendtime = outputs($query);
            if($findendtime){
                $findendtime = $findendtime->fetch_array(MYSQLI_ASSOC);
                $start_time = isset($findendtime['end_time']) ? strtotime("+0 seconds", strtotime($findendtime['end_time'])) : $start_time;
            }
        }

        $slot_end_time = strtotime("+30 minutes", strtotime($start_time));
        $slot_end_time = date('H:i:s', $slot_end_time);
        $is_first = true;
        foreach ($flight_book_id as $user_row_key => $user_row_value) {
            // $flight_book_id = $flight_book_id[$user_row_key];
            $customer_id = $slot_holders[$user_row_key];
            $duration = $durations[$user_row_key];
            if (isset($available_time[$flight_time]) && $available_time[$flight_time] < $duration) { // for check the time availability
                continue;
            }
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

function reArrange($updatedSlots){
    $bid = array_column($updatedSlots, 'frid');
    $bid = implode(",", $bid);
    $query = "SELECT DISTINCT(slot_start_time) as slot_start_time FROM `flight_rotation` WHERE flight_book_id IN (".$bid.") GROUP BY slot_start_time;";
    $queryResult = outputs($query);
    if($queryResult && $queryResult->num_rows > 0){
        $slotData = $queryResult->fetch_all(MYSQLI_ASSOC);
        $slotData = array_column($slotData, 'slot_start_time');
        $slotData = array_map(function($value) {
            return "'$value'";
        }, $slotData);
        $slotData = implode(",", $slotData);
        $query = "SELECT DISTINCT(slot_start_time) as slot_start_time, GROUP_CONCAT(id) as id,GROUP_CONCAT(customer_id) as customer_id, GROUP_CONCAT(duration) as duration FROM `flight_rotation` WHERE slot_start_time IN (".$slotData.") AND `status` NOT IN ('hold') GROUP BY slot_start_time ORDER BY order_by ASC;";
        $queryResult = outputs($query);
        if($queryResult && $queryResult->num_rows > 0){
            $data = $queryResult->fetch_all(MYSQLI_ASSOC);
            foreach ($data as &$row) {
                foreach (['id', 'customer_id', 'duration'] as $key) {
                    $row[$key] = explode(",", $row[$key]);
                }
            }
            
            foreach($data as $k => $row){
                $is_first = true;
                $ai = 1;
                $temp_row = [];
                foreach ($row['id'] as $key => $user_row) {
                    if ($is_first === false) {
                        $start_time = $end_time;
                    } else {
                        $start_time = new DateTime($row['slot_start_time']);
                        $start_time = $start_time->format('H:i:s');
                    }
                    $duration = $row['duration'][$key];
                    $end_time = strtotime("+" . $duration . " minutes", strtotime($start_time));
                    $end_time = date('H:i:s', $end_time);
                    $temp_row[$user_row]['start_time'] = $start_time;
                    $temp_row[$user_row]['end_time']   = $end_time;
                    $temp_row[$user_row]['order_by']   = $ai;
                    $is_first = false;
                    inputs("UPDATE flight_rotation SET start_time = '$start_time', end_time = '$end_time', order_by = $ai WHERE id = $id;");
                    ++$ai;
                }                
            }
        }
    }
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
    foreach ($bookings as $val) {
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
    $sql1 = "INSERT INTO flight_booking_cron (flight_bookings_id, schedule_time, duration, created_date) VALUES ";
    $now = date("Y-m-d");
    $firstElements = array_map(function ($item) use ($now) {
        $string = preg_replace("/[()]/", "", $item);
        $array = array_map('trim', explode(',', preg_replace("/'/", "", $string)));
        return "('" . $array[0] . "', '" . $array[2] . "', '" . $array[4] . "', '" . $now . "')";
    }, $values);

    $sql1 .= implode(',', $firstElements);
    inputs($sql1);
}

