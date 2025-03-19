<?php
ini_set('max_execution_time', 0);

    date_default_timezone_set("Asia/Dubai");

header("Access-Control-Allow-Origin: *");  // Allow any origin (adjust if needed)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");  // Allow the necessary HTTP methods
header("Access-Control-Allow-Headers: Content-Type, Authorization");  // Allow Content-Type header and others if needed
header("Access-Control-Allow-Credentials: true");  // Include if needed for credentials
require_once 'dbConfig.php';

$today = date("Y-m-d H:i:s");
$yesterday = date("Y-m-d H:i:s", strtotime("-1 days"));



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
    if ($ok) {
        $num_rows = mysqli_num_rows($ok);
        return  $num_rows;
    } else {
        return 0;
    }
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

function insertMultipleRecords($table, $data) {
    global $sqli;
    if (empty($data)) {
        die("Data array is empty. Cannot insert.");
    }

    $columns = array_keys($data[0]);
    $columnsWithBackticks = array_map(function($col) {
        return "`$col`";
    }, $columns);
    $columns = implode(", ", $columnsWithBackticks);
    $placeholders = implode(", ", array_fill(0, count($data[0]), "?"));
    $sql = "INSERT INTO `$table` ($columns) VALUES ($placeholders)";
    $stmt = $sqli->prepare($sql);

    if (!$stmt) {
        die("Prepare failed: " . $sqli->error . " - SQL: $sql");
    }

    foreach ($data as $row) {
        $values = array_values($row);
        $types = '';
        foreach ($values as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } elseif (is_string($value)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
        }

        $stmt->bind_param($types, ...$values);

        if (!$stmt->execute()) {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        }
    }

    $stmt->close();
    // echo "Records inserted successfully.";
}

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


function callProcedure($id)
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

function getArray($result) : array {
    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            return [];
        }
    } else {
        return [];
    }
}

mysqli_set_charset($sqli, "utf8");


function getToken($length)
{
    $token = "";
    $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $codeAlphabet .= "0123456789";
    $max = strlen($codeAlphabet); // edited

    for ($i = 0; $i < $length; $i++) {
        $token .= $codeAlphabet[random_int(0, $max - 1)];
    }

    return $token;
}


function success($message, $data = [])
{
    $json["status"] = "success";
    $json["message"] = $message;
    $json["data"] = $data;
    die(json_encode($json));
}

function error($message, $data = [])
{
    $json["status"] = "fail";
    $json["message"] = $message;
    $json["data"] = $data;
    die(json_encode($json));
}

function none($message)
{
    $json["status"] = "empty";
    $json["message"] = $message;
    die(json_encode($json));
}
