<?php
function encodeVideoName($videoName, $salt="jinson") {
    // Generate a hash using a combination of the video name and the salt
    return hash('sha256', $salt . $videoName);
}

function encodeVideoName($videoName, $salt="jinson") {
    // Generate a hash using a combination of the video name and the salt
    return hash('sha256', $salt . $videoName);
}