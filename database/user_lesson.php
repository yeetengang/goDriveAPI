<?php

// Create Table if not exist
// ******** user_lesson ************ //
$sql = "CREATE TABLE if not exists `user_lesson` (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    lesson_id INT(11) NOT NULL
)";

if ($conn->query($sql) != TRUE) {
    echo "Error creating table: " . $conn->error;
} 

?>