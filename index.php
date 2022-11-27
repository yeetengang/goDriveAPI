<?php

require 'config.php';
// To show the potential errors (for dev only)
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

include './database/DbConnect.php';
$objDb = new DbConnect;
$conn = $objDb->connect('godrive'); 

include './database/lessons.php';
include './database/users.php';
include './database/user_exam.php';
include './database/user_lesson.php';

$method = $_SERVER['REQUEST_METHOD'];

$uri = $_SERVER['REQUEST_URI'];
$path= explode('/', $uri); //explode to see which php to be include

switch ($path[2]) {
    case 'user':
        require './internal/users/index.php';
        break;
    case 'lesson':
        require './internal/lessons/index.php';
}
?>