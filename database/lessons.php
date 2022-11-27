<?php

// Create Table if not exist
// ******** lessons ************ //
$sql = "CREATE TABLE if not exists `lessons` (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    state VARCHAR(50) NOT NULL,
    type VARCHAR(50) NOT NULL,
    status TINYINT(1) NOT NULL,
    venue VARCHAR(50) NOT NULL,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    slots INT(11) NOT NULL
)";

if ($conn->query($sql) != TRUE) {
    echo "Error creating table: " . $conn->error;
} 
?>