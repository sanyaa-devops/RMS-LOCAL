<?php
// Start the PHP session if needed
session_start();

$filesToInclude = ['config.php', 'functions.php'];
$baseDir = dirname(__DIR__)."/includes/";




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


$defaultPage = 'login';
$validPages = ['login', 'operator', 'reception'];

$page = isset($_GET['page']) ? filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING) : $defaultPage;
$filePath = dirname(__DIR__) . "/views/{$page}.php";

if (in_array($page, $validPages) && file_exists($filePath)) {
    require $filePath;
} else {
    error_log("View file not found: " . $filePath);
    header("HTTP/1.0 404 Not Found");
    require dirname(__DIR__) . '/views/error_404.php';
}
// Include the header
// Dynamically include content based on request (can handle routing here)
/* if (isset($_GET['page'])) {
    $page = $_GET['page'];
    
    // Sanitize the input
    $page = filter_var($page, FILTER_SANITIZE_STRING);
    
    // Check the page and include corresponding view
    switch ($page) {
        
        case '/':
            require dirname(__DIR__).'/views/login.php';
            break;
        case 'login':
            require dirname(__DIR__).'/views/login.php';
            break;
        case 'operator':
            require dirname(__DIR__).'/views/operator.php';
            break;  
        case 'reception':
            require dirname(__DIR__).'/views/reception.php';
            break;       
        default:
            header("HTTP/1.0 404 Not Found");
            require dirname(__DIR__).'/views/error_404.php';
            break;
    }
} else {
    require dirname(__DIR__).'/views/login.php';
} */

// Include the footer

?>
