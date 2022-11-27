<?php

// Create Table if not exist
// ******** users ************ //
$sql = "CREATE TABLE if not exists `users` (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(60) NOT NULL,
    password VARCHAR(60) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    login_at INT(20) NOT NULL,
    license_type CHAR(1) NOT NULL,
    valid_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    valid_end TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_admin TINYINT(1) NOT NULL
)";

if ($conn->query($sql) != TRUE) {
    echo "Error creating table: " . $conn->error;
}  else {
    // Create admin account if admin account not exist
    $sql = "SELECT * FROM users WHERE is_admin = TRUE";
    $statement = $conn->prepare($sql);
    if ($statement->execute()) {
        $result = $statement->get_result();
        $user = array();
        while($row = $result->fetch_assoc()) {
            $user[] = $row;
        }
        if (sizeof($user) == 0) {
            // admin account not exists
            $sql = "INSERT INTO users(email, password, created_at, license_type, is_admin) VALUES(?, ?, ?, ?, ?)";
            
            $email = "superadmin@gmail.com";
            $password = "superadmin";
            $created_at = date('Y-m-d h:i:sa');
            $license_type = "N";
            $is_admin = 1;

            $statement = $conn->prepare($sql);
            $statement->bind_param('ssssi', $email, $password, $created_at, $license_type, $is_admin);

            $statement->execute();
        } 
    }
}
?>