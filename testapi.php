<?php
$url = "https://store.inflightdubai.com/inflight/main/api.php";
    $postData = [
        'call' => 'getFlightBookingsCron',
        'flight_date' => '2024-12-04',
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
    print_r($response);
?>