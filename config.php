<?php

error_reporting(0);
session_start();

/* Config */
$key = bin2hex(random_bytes(32));
define("SITE_KEY", 'NcQfTjWnZr4u7x!A%D*G-KaPdSgUkXp2s5v8y/B?E(H+MbQeThWmYq3t6w9z$C&F'); 

/* API key encryption */
function apiToken($session_uid)
{
    $key=md5(SITE_KEY.$session_uid);
    return hash('sha256', $key);
}

/* password config */
function passHash($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function passVeri($password, $dbhash) {
    // Take db stored hash to verify with the input password
    return password_verify($password, $dbhash);
}

//checkTimeout(strtotime('2022-11-19 10:39:43'), 60000)
function checkTimeout($t, $expire) {
    $timenow=time();

    if (($timenow - $t) < ($expire/1000)) {
        return true; //No timeout
    }
    return false; //timeout
}