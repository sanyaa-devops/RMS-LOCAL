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
    $currentDate = $date; 
   $videoFiles = getAllVideoFiles($videoLoc,$currentDate );
   
    echo json_encode($videoFiles);
    exit;
}

function validateUser($userId){
    $query = "SELECT * FROM `customer` WHERE customer_id = '$userId' ";
    $result = outputs($query);
     $user = $result->fetch_assoc();
    return (bool) $user;
   
}
function getAllVideoFiles($folderPath,$currentDate) {
    // Define an array of common video file extensions
    $videoExtensions = ['mp4', 'MP4'];

    // Get the current date in the format 'd-m-Y' (same as how it's stored in file creation)
     // Get today's date

    // Initialize an array to store video files
    $videoFiles = [];

    // Initialize RecursiveDirectoryIterator and limit the depth
    $directoryIterator = new RecursiveDirectoryIterator($folderPath);
    $iterator = new RecursiveIteratorIterator($directoryIterator);

    foreach ($iterator as $file) {
        // Limit depth to 2 levels
        if ($iterator->getDepth() > 2) {
            continue;
        }

        if ($file->isFile()) {
            // Get the file extension
            $extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);

            // Check if the file has a valid video extension
            if (in_array($extension, $videoExtensions)) {
                // Get the directory of the file
                $dirPath = dirname($file->getPathname());

                // Get the previous folder (user ID)
                $pathParts = explode(DIRECTORY_SEPARATOR, $dirPath);
                $previousFolder = (count($pathParts) > 1) ? $pathParts[count($pathParts) - 2] : null;
                $previousFolder1 = (count($pathParts) > 1) ? $pathParts[count($pathParts) - 1] : null;
                // Get the creation date of the file
                $fileCreatedDate = date('d-m-Y', filectime($file->getPathname()));

                // If the current date is set (not null), filter by date
                if ($currentDate && $previousFolder1 == $currentDate) {
                    $videoFiles[] = [
                        'name' => base64_encode(pathinfo($file->getFilename(), PATHINFO_FILENAME)), // Get only the base name
                        'date' => base64_encode($fileCreatedDate), // Assign the file creation date,
                        'path' => base64_encode($previousFolder), // Base64 encode the folder name (user ID)
                        'fullpath' =>base64_encode($previousFolder.'/'.$previousFolder1.'/'.pathinfo($file->getFilename(), PATHINFO_FILENAME).'.mp4')// Full path of the file
                    ];
                }
                // If no current date (e.g., no date filter), return all video files
                elseif (!$currentDate) {
                    $videoFiles[] = [
                        'name' => base64_encode(pathinfo($file->getFilename(), PATHINFO_FILENAME)),
                        'date' => base64_encode($fileCreatedDate),
                        'path' => base64_encode($previousFolder),
                       'fullpath' =>base64_encode($previousFolder.'/'.$previousFolder1.'/'.pathinfo($file->getFilename(), PATHINFO_FILENAME).'.mp4')// Full path of the file
                    ];
                }
            }
        }
    }

    // Return the video files as JSON
    echo json_encode($videoFiles, JSON_PRETTY_PRINT);
    exit;
}

function getAllVideoFiles2($folderPath) {
     // Define an array of common video file extensions
     $videoExtensions = ['mp4', 'MP4'];

     // Initialize an array to store video files
     $videoFiles = [];
 
     // Initialize RecursiveDirectoryIterator and limit the depth
     $directoryIterator = new RecursiveDirectoryIterator($folderPath);
     $iterator = new RecursiveIteratorIterator($directoryIterator);
 
     foreach ($iterator as $file) {
        
         // Limit depth to 2 levels
         if ($iterator->getDepth() > 2) {
             continue;
         }
 
         if ($file->isFile()) {
             // Get the file extension
             $extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
 
             // Check if the file has a valid video extension
             if (in_array($extension, $videoExtensions)) {
                $dirPath = dirname($file->getPathname());

                // Split the directory path into an array of folders
                $pathParts = explode(DIRECTORY_SEPARATOR, $dirPath);

                // Check if there's a folder before the current folder
                $previousFolder = (count($pathParts) > 1) ? $pathParts[count($pathParts) - 2] : null;
                $fileCreatedDate = base64_encode(date('d-m-Y', filectime($file->getPathname())));
               // $parentDir = $iterator->getSubIterator(1)->getFilename();
                 $videoFiles[] = [
                     'name' => base64_encode(pathinfo($file->getFilename(), PATHINFO_FILENAME)), // Get only the base name
                     'date' => $fileCreatedDate, // Assign the current date,
                     'path' =>  base64_encode($previousFolder),
                     'fullpath'=>$dirPath
                 ];
             }
         }
     }
 
     // Return the video files as JSON
     echo json_encode($videoFiles, JSON_PRETTY_PRINT);
     exit;
 
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
            $videoFiles[] = [
                'name' => base64_encode(pathinfo($file, PATHINFO_FILENAME)), // Get only the basename
                'date' => base64_encode($date) // Assign the current date
            ];
        }
    }

    

    echo json_encode($videoFiles);
    exit;
}





