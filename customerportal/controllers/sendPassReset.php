<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true"); 

require_once 'config.php';
include_once 'newdb.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    $email =$data['email'];
    if (isset($email)) {
        $result = resetPassword($email);
        echo json_encode($result);
    }else{
        echo json_encode(['success'=>0,'msg'=>'Unable to update']);
    }
    die;
}

function resetPassword($email){
    $url = "https://store.inflightdubai.com/inflight/main/api.php";
    $postData = [
        'call' => 'sendPassReset',
        'email' => $email,
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
        return false;
    }
    curl_close($ch);
    $response = json_decode($response, true);
    return $response;
}