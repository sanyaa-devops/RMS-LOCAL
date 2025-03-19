<?php

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];


$project_folder = "/inflightdubai/reception/public";



$baseurl = $protocol . $host . $project_folder;

define('BASE_URL', $baseurl);


