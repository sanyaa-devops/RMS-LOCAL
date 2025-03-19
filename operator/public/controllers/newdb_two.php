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

function getConnected_two($host, $user, $pass, $db)
{
    $mysqli = new mysqli($host, $user, $pass, $db);

    if ($mysqli->connect_error)
        die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());

    return $mysqli;
}

function getCount_two($id)
{
    $ok = outputs_two($id);
    if ($ok) {
        $num_rows = mysqli_num_rows($ok);
        return  $num_rows;
    } else {
        return 0;
    }
}

function safex_two($id)
{
    global $sqli_two;

    $cat = mysqli_real_escape_string($sqli_two, $id);
    return $cat;
}

setlocale(LC_ALL, 'en_US.UTF8');
function uri_two($str, $replace = array(), $delimiter = '-')
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
$sqli_two = getConnected_two($db->active->host_two, $db->active->user_two, $db->active->password_two, $db->active->db_two);

mysqli_query($sqli_two, 'SET NAMES utf8');
function inputs_two($id)
{
    global $sqli_two;
    $sql = $id;
    $result = mysqli_query($sqli_two, $sql);
    if ($result) {
        return $result;
    } else {
        return false;
    }
}

function callProcedure_two($id)
{
    global $sqli_two;
    $sql = $id;
    $result = mysqli_query($sqli_two, $sql);
    if ($result) {
        return $result;
    } else {
        return false;
    }
}

function outputs_two($id)
{
    global $sqli_two;
    $result = mysqli_query($sqli_two, $id);
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

function exists_two($id)
{
    global $sqli_two;
    $result = mysqli_query($sqli_two, $id);
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

function logit_two($text)
{
    $date = date('d/m/Y h:i:s a', time());
    $file = $_SERVER["SCRIPT_NAME"];
    file_put_contents("logs.txt", PHP_EOL . $file . " --> " . $date, FILE_APPEND);
    file_put_contents("logs.txt", PHP_EOL . $text . PHP_EOL . "----------------------------------------------", FILE_APPEND);
}

mysqli_set_charset($sqli_two, "utf8");

function getToken_two($length)
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

function success_two($message, $data = [])
{
    $json["status"] = "success";
    $json["message"] = $message;
    $json["data"] = $data;
    die(json_encode($json));
}

function error_two($message, $data = [])
{
    $json["status"] = "fail";
    $json["message"] = $message;
    $json["data"] = $data;
    die(json_encode($json));
}

function none_two($message)
{
    $json["status"] = "empty";
    $json["message"] = $message;
    die(json_encode($json));
}
