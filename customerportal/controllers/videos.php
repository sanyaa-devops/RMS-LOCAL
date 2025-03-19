<?php
session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true"); 
require_once 'config.php';
if(!$_SESSION || !$_SESSION['user_id']){
    session_destroy();
    header("location: {$endpoint}login.php");
    exit;
}
include_once 'newdb.php';

$data = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Define the folder path for videos
  
    // Get posted data
    $data = $_POST;
    $name = $data['videoName'] ?? null;
    $userId = $data['username'] ?? null; // Using null coalescing operator for safety
    $date = $data['date']??null; // Default to current date if not provided

    // Validate user ID
    if (!$userId || !validateUser($userId)) {
        header("location: {$endpoint}login.php");
        exit; // Stop further script execution after redirect
    }
    if (!$name) {
        header("location: {$endpoint}dashboard.php");
        exit; // Stop further script execution after redirect
    }
    $name = base64_decode($name);
    $date = base64_decode($date);
    
    // Construct path for user's videos
    $path = "{$folderPath}/{$userId}/{$date}/{$name}.mp4";

    // Get video files and return JSON response
    $videoFiles = $path;
    echo json_encode($videoFiles);
    exit;
}

function validateUser($userId){
    $query = "SELECT * FROM `customer` WHERE customer_id = '$userId' ";
    $result = outputs($query);
     $user = $result->fetch_assoc();
    return (bool) $user;
   
}







