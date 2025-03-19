<?php

date_default_timezone_set("Asia/Dubai");

header("Access-Control-Allow-Origin: *");  // Allow any origin (adjust if needed)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");  // Allow the necessary HTTP methods
header("Access-Control-Allow-Headers: Content-Type, Authorization");  // Allow Content-Type header and others if needed
header("Access-Control-Allow-Credentials: true");  // Include if needed for credentials
require_once 'dbConfig.php';

function getConnected($host, $user, $pass, $db)
{
    $mysqli = new mysqli($host, $user, $pass, $db);
    if ($mysqli->connect_error)
        die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
    return $mysqli;
}

function getCount($id)
{
    $ok = outputs($id);
    $count = mysqli_fetch_assoc($ok);
    $finalcount  = $count["COUNT(1)"];
    return $finalcount;
}

function safex($id)
{
    global $sqli;

    $cat = mysqli_real_escape_string($sqli, $id);
    return $cat;
}


setlocale(LC_ALL, 'en_US.UTF8');
function uri($str, $replace = array(), $delimiter = '-')
{


    $str = str_replace("&#39;", "", $str);
    $str = str_replace("&amp;", "and", $str);


    $str = preg_replace("/&#?[a-z0-9]{2,8};/i", "", $str);

    if (!empty($replace)) {
        $str = str_replace((array)$replace, ' ', $str);
    }

    $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
    $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
    $clean = strtolower(trim($clean, '-'));
    $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
    $clean = trim(preg_replace('/-+/', '-', $clean), '-');
    return $clean;
}

$db = new DBConfig();
$sqli = getConnected($db->active->host, $db->active->user,$db->active->password, $db->active->db);
mysqli_query($sqli, 'SET NAMES utf8');
function inputs($id)
{
    global $sqli;
    $sql = $id;
    $result = mysqli_query($sqli, $sql);
    if ($result) {
        return $result;
    } else {
        return false;
    }
}





function outputs($id)
{
    global $sqli;
    $result = mysqli_query($sqli, $id);
    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            return $result;
        } else {
            return false;
        }
    } else {
        // Get the current timestamp
        $timestamp = date("Y-m-d H:i:s");
        // Get the error message
        $errorMessage = "Query Error: " . mysqli_error($sqli);
        // Specify the complete path to the log file
        $logFilePath = '../queryerror/queryerror.txt';
        // Append the error information to the log file
        $logMessage = "$timestamp - ID: $id - $errorMessage\n";
        file_put_contents($logFilePath, $logMessage, FILE_APPEND);
        return false;
    }
} // function




function exists($id)
{
    global $sqli;
    $result = mysqli_query($sqli, $id);
    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            return $result;
        } else {
            return false;
        }
    } else {
        // Get the current timestamp
        $timestamp = date("Y-m-d H:i:s");
        // Get the error message
        $errorMessage = "Query Error: " . mysqli_error($sqli);
        // Specify the complete path to the log file
        $logFilePath = '../queryerror/queryerror.txt';
        // Append the error information to the log file
        $logMessage = "$timestamp - ID: $id - $errorMessage\n";
        file_put_contents($logFilePath, $logMessage, FILE_APPEND);
        return false;
    }
} // function


function logit($text)
{

    $date = date('d/m/Y h:i:s a', time());
    $file = $_SERVER["SCRIPT_NAME"];
    file_put_contents("logs.txt", PHP_EOL . $file . " --> " . $date, FILE_APPEND);
    file_put_contents("logs.txt", PHP_EOL . $text . PHP_EOL . "----------------------------------------------", FILE_APPEND);
}







mysqli_set_charset($sqli, "utf8");
