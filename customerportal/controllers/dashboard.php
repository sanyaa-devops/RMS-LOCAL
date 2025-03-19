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
    
    $data = $_POST;
    $userId = $data['username'] ?? null; // Using null coalescing operator for safety
    $date = $data['date'] ?? date('d-m-Y'); // Default to current date if not provided

    // Validate user ID
    if (!$userId) {
        header("location: {$endpoint}login.php");
        exit; // Stop further script execution after redirect
    }

    // Construct path for user's videos
    $path = "{$videoLoc}/{$userId}/{$date}/";
   
    // Get video files and return JSON response
    $videoFiles = getVideoFiles($path, $date);
   
    echo json_encode($videoFiles);
    exit;
}

function validateUser($userId){
    $query = "SELECT * FROM `customer` WHERE customer_id = '$userId' ";
    $result = outputs($query);
     $user = $result->fetch_assoc();
    return (bool) $user;
   
}

function getVideoFiles($folderPath,$date) {
   
    // Define an array of common video file extensions
    $videoExtensions = ['mp4','MP4'];

    // Initialize an array to store video files
    $videoFiles = [];

    // Loop through each video extension
    foreach ($videoExtensions as $extension) {
        // Use glob to find all video files with the current extension
        $files = glob("{$folderPath}/*.$extension");

        // Merge the found files with the videoFiles array
        //$videoFiles = array_merge($videoFiles, $files);
        foreach ($files as $file) {
			$fileDate = date("d-m-Y H:i:s", filectime($file));
            $videoFiles[] = [
                'name' => base64_encode(pathinfo($file, PATHINFO_FILENAME)), // Get only the basename
                'date' => base64_encode($date),
                'fileDate' => base64_encode($fileDate) // Assign the current date
            ];
        }
    }

    

    echo json_encode($videoFiles);
    exit;
}



