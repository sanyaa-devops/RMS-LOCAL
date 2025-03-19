<?php
session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true"); 
require_once 'config.php';
if(!$_SESSION || !$_SESSION['user_id'] ||!validateUser($_SESSION['user_id'])){
    session_destroy();
    header("location: {$endpoint}login.php");
    exit;
}
$name = $_GET['s']??'';
$date = $_GET['d']??'';
$userId = $_SESSION['user_id'];



if (!$name||!$date) {
    header("location: {$endpoint}/dashboard.php");
    exit; // Stop further script execution after redirect
} 

$name = base64_decode($name);
$date = base64_decode($date);

$videoFilePath = "{$videoLoc }/{$userId}/{$date}/{$name}.mp4";


// Check if the file exists
if (file_exists($videoFilePath)) {
    // Set headers for video streaming
    header('Content-Type: video/mp4');
    header('Content-Disposition: inline; filename="' . basename($videoFilePath) . '"');
    header('Content-Length: ' . filesize($videoFilePath));
    readfile($videoFilePath);
    exit;
} else {
    echo 'Video not found';
}


function validateUser($userId){
    $query = "SELECT * FROM `customer` WHERE customer_id = '$userId' ";
    $result = outputs($query);
     $user = $result->fetch_assoc();
    return (bool) $user;
   
}
