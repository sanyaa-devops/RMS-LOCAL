<?php
// Start the PHP session if needed
session_start();

$filesToInclude = ['config.php', 'functions.php'];

$baseDir = '../includes/';


if($filesToInclude){
    foreach ($filesToInclude as $file) {
        $filePath = $baseDir . $file;
        if (file_exists($filePath)) {
            require_once $filePath; // Use require_once to prevent multiple inclusions
        } else {
            // Handle the error, e.g., log it or display a message
            error_log("File not found: " . $filePath);
        }
    }
}


// Include the header
// Dynamically include content based on request (can handle routing here)
if (isset($_GET['page'])) {
    $page = $_GET['page'];
    
    // Sanitize the input
    $page = filter_var($page, FILTER_SANITIZE_STRING);
    
    // Check the page and include corresponding view
    switch ($page) {
        
        case '/':
        case 'reception':
            require '../views/reception.php';
            break;       
        default:
            header("HTTP/1.0 404 Not Found");
            require '../views/error_404.php';
            break;
    }
} else {
    require '../views/reception.php';
  
}
?>
