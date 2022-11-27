<?php

// Create Table if not exist
// ******** user_exam ************ //
$sql = "CREATE TABLE if not exists `user_exam` (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    exam_type VARCHAR(11) NOT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) != TRUE) {
    echo "Error creating table: " . $conn->error;
} 

?>