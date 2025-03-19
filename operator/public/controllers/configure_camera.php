<?php
ini_set('max_execution_time', 180);
error_reporting(0);

header("Access-Control-Allow-Origin: http://127.0.0.1:5000");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400");

// Set PHP to detect user abort
ignore_user_abort(false);

// Define the command to execute the Python script
$command = 'python E:\\xampp\\htdocs\\inflightdubai\\pythonscript\\connection_to_router.py 2>&1';

// Create a process
$process = popen($command, 'r');

if (is_resource($process)) {
    while (!feof($process)) {
        // Check connection status
        if (connection_aborted()) {
            pclose($process); // Close the Python process
            exit("Browser closed. Process terminated.");
        }

        $output = fgets($process); // Read output line by line
        if ($output) {
            echo "<pre>$output</pre>"; // Display the output
            flush(); // Flush the output buffer
        }
    }
    pclose($process); // Close the process
} else {
    echo "Failed to execute the script.";
}
