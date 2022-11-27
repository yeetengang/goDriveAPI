<?php

switch($method) {
    //HTTP Method
    case "GET":
        $sql = "SELECT id, email, created_at, updated_at, license_type, valid_start, valid_end FROM users WHERE is_admin=FALSE";
        $path= explode('/', $_SERVER['REQUEST_URI']);

        if (isset($path[3]) && is_numeric($path[3])) {
            /*  Get specific user data via id(indexing)  */
            $sql .= " AND id = ?";
            $statement = $conn->prepare($sql);
            
            //i = INT
            $statement->bind_param("i", $path[3]);
            $statement->execute();

            $result = $statement->get_result();
            $user = array();
            while($row = $result->fetch_assoc()) {
                $user[] = $row;
            }
            echo json_encode($user[0]);
        } else {
            /* Get all users data */
            $statement = $conn->prepare($sql);
            $statement->execute();
            /*$result = mysqli_query($conn, $sql) or die("Error in Selecting " . mysqli_error($conn));*/
            $result = $statement->get_result();
            $users = array();
            while($row = $result->fetch_assoc())
            {
                $users[] = $row;
            }
            echo json_encode($users);
        }
        
        break;

    case "POST":
        $user = json_decode(file_get_contents('php://input'));
        if ($path[3] == "retrieve" || $path[3] == "admin") {
            /* Retrieve admin and user login token */
            if ($path[3] == "retrieve") {
                $sql = "SELECT * FROM users WHERE email=? LIMIT 1";
            } else {
                $sql = "SELECT * FROM users WHERE email=? AND is_admin=TRUE LIMIT 1";
            }

            $statement = $conn->prepare($sql);
            $statement->bind_param("s", $user->email);
            $statement->execute();

            $result = $statement->get_result();
            $details = array();
            while ($row = $result->fetch_assoc()) {
                $details[] = $row;
            }
            $id = $details[0]['id'];
            $hash = $details[0]['password'];
            if (!is_null($id) && passVeri($user->password, $hash)) {
                // If user authorized then update database for login time
                $sql = "UPDATE users SET login_at=? WHERE id = ?";
                $t=time(); //timestamp when user login
                $statement = $conn->prepare($sql);
                $statement->bind_param('ii', $t, $id);
                $statement->execute();

                $token = apiToken($t);
                $response = ["expire"=>(24 * 60 * 60 * 1000), "token"=>$token, "id"=>$id]; //expire: time for token to expire (Hours to expire * Minutes * Seconds * 1000)
                
            } else {
                $response = ["expire"=>(2000), "token"=>"", "id"=>""];
            }
            echo json_encode($response);
            
        } else if ($path[3] == "lesson") {
            /* To register lesson for that user */
            $token = $user->token;
            $sql = "SELECT login_at, license_type, valid_end FROM users WHERE id=? LIMIT 1";
            $statement = $conn->prepare($sql);
            $statement->bind_param("i", $user->userid);
            $statement->execute();

            $result = $statement->get_result();
            $items = array();
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
            //echo json_encode($items);
            $login_at = $items[0]['login_at'];
            $license_type = $items[0]['license_type'];
            $valid_end = strtotime($items[0]['valid_end']);

            $t = time();
            if (($valid_end - $t) > 0) {
                $valid = TRUE;
            } else {
                $valid = FALSE;
            }

            $real_token = apiToken($login_at);
            if ($real_token == $token && $license_type == 'L' && $valid) {
                /* If the user have valid token and valid license */
                $sql = "INSERT INTO user_lesson(user_id, lesson_id) VALUES(?, ?)";
                $statement = $conn->prepare($sql);
                $statement->bind_param('ii', $user->userid, $user->lessonid);
                if ($statement->execute()) {
                    $response = ['status'=>1, 'message'=>'Record created successfully'];
                } else {
                    $response = ['status'=>0, 'message'=>'Failed to create record'];
                }
                
            } else {
                $response = ['status'=>0, 'message'=>'Failed to create record'];
            }
            echo json_encode($response);
        } else if ($path[3] == "retrievelesson") {
            /* To get the list of lessons registered by student */
            $token = $user->token;
            $id = $user->id;
            
            $sql = "SELECT login_at FROM users WHERE id=$id LIMIT 1";
            $statement = $conn->prepare($sql);
            $statement->execute();

            $result = $statement->get_result();
            $items = array();
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
            $login_at = $items[0]['login_at'];

            
            $real_token = apiToken($login_at);

            if ($real_token == $token) {
                $sql = "SELECT lesson_id FROM user_lesson WHERE user_id=$id";
                $statement = $conn->prepare($sql);
                $statement->execute();

                $lesson_result = $statement->get_result();
                $lesson_ids = array();
                while ($row = $lesson_result->fetch_assoc()) {
                    $lesson_ids[] = $row;
                }
                if (sizeof($lesson_ids) > 0) {
                    $num = "";
                    foreach ($lesson_ids as $id ) {
                        $num = $num.strval($id['lesson_id']).',';
                    }
                    $lessons = substr($num, 0, -1);
                    $sql = "SELECT * FROM lessons where id in ({$lessons})";
                    $statement = $conn->prepare($sql);
                    $statement->execute();

                    $lesson_details_result = $statement->get_result();
                    $lesson_details = array();
                    while ($row = $lesson_details_result->fetch_assoc()) {
                        $lesson_details[] = $row;
                    }
                    echo json_encode($lesson_details);
                } else {
                    echo json_encode([]);
                }
            }
        } else if ($path[3] == "retrievelicense") {
            /* To get the license details of that user */
            $token = $user->token;
            $id = $user->id;
            
            $sql = "SELECT login_at, license_type, valid_start, valid_end FROM users WHERE id=$id LIMIT 1";
            $statement = $conn->prepare($sql);
            $statement->execute();

            $result = $statement->get_result();
            $items = array();
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
            $login_at = $items[0]['login_at'];
            $real_token = apiToken($login_at);
            
            $valid_start = explode(' ', $items[0]['valid_start'])[0];
            $valid_end = explode(' ', $items[0]['valid_end'])[0];

            $items[0]['valid_start'] = $valid_start;
            $items[0]['valid_end'] = $valid_end;

            if ($real_token == $token) {
                echo json_encode($items[0]);
            }
        } else if ($path[3] == "exam") {
            /* To register exam for user */
            $token = $user->token;
            $sql = "SELECT login_at, license_type, valid_end FROM users WHERE id=? LIMIT 1";
            $statement = $conn->prepare($sql);
            $statement->bind_param("i", $user->id);
            $statement->execute();

            $result = $statement->get_result();
            $items = array();
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
            $login_at = $items[0]['login_at'];
            $license_type = $items[0]['license_type'];
            $valid_end = strtotime($items[0]['valid_end']);

            $t = time();
            if (($valid_end - $t) > 0) {
                $valid = TRUE;
            } else {
                $valid = FALSE;
            }
            /* Only License P user can register driving test, All License Type (L, P, N) can register KPP test */
            $exam_type = $user->exam_type;
            $real_token = apiToken($login_at);
            $registered_at = date('Y-m-d h:i:sa');
            $sql = "INSERT INTO user_exam(user_id, exam_type, registered_at) VALUES(?, ?, ?)";
            $message ="";
            $statement = $conn->prepare($sql);
            if ($exam_type == "KPP" && $real_token == $token) {
                $statement->bind_param('iss', $user->id, $exam_type, $registered_at);
            } else if ($exam_type == "Driving" && $real_token == $token && $license_type == 'P' && $valid) {
                $statement->bind_param('iss', $user->id, $exam_type, $registered_at);
            } else {
                $message = "No valid license for this exam";
            }
            if ($statement->execute()) {
                $response = ['status'=>1, 'message'=>'Record created successfully'];
            } else {
                $response = ['status'=>0, 'message'=>'Failed to create record, '.$message];
            }
            echo json_encode($response);
        }
        else if ($path[3] == 'email') {
            /* Send Email from the provided details*/
            $name = $user->name;
            $email = $user->email;
            $phone = $user->phone;
            $message = $user->message;
            $receiver = ""; // App manage people's email

            $subject = 'GoDrive - Get In Touch';
            $returnMessage = $message."\n\nBy ".$name."\n"."Phone Number: ".$phone."\nEmail: ".$email;

            if(mail($receiver, $subject, $returnMessage)) {
                $response = ['status'=>1, 'message'=>'Email sent successfully'];
            } else {
                $response = ['status'=>0, 'message'=>'Failed to create and send email'];
            }
            echo json_encode($response);
        }
        else {
            /* Create New User, New User Wont have License Type */
            $sql = "INSERT INTO users(email, password, created_at, license_type) VALUES(?, ?, ?, ?)";
            $created_at = date('Y-m-d h:i:sa');
            $license_type = "N";
            $passhash = passHash($user->password);

            $statement = $conn->prepare($sql);
            // sss = STRING, STRING, STRING
            $statement->bind_param('ssss', $user->email, $passhash, $created_at, $license_type);

            if($statement->execute()) {
                $response = ['status'=>1, 'message'=>'Record created successfully'];
            } else {
                $response = ['status'=>0, 'message'=>'Failed to create record'];
            }
            echo json_encode($response);
        }

        break;

    case "PUT":
        /* Update specific user */
        $id= explode('/', $_SERVER['REQUEST_URI'])[3];
        $user = json_decode(file_get_contents('php://input'));
        $sql = "UPDATE users SET license_type=?, valid_start=?, valid_end=?, updated_at=? WHERE id = ?";
        $updated_at = date('Y-m-d h:i:sa');

        $statement = $conn->prepare($sql);
        // sssdi = STRING, STRING, STRING, DATE, INT
        $statement->bind_param('ssssi', $user->license_type, $user->valid_start, $user->valid_end, $updated_at, $id);

        if($statement->execute()) {
            $response = ['status'=>1, 'message'=>'Record updated successfully'];
        } else {
            $response = ['status'=>0, 'message'=>'Failed to update record'];
        }
        
        echo json_encode($response);
        break;

    case "DELETE":
        $sql = "DELETE FROM users WHERE id = ? AND is_admin=FALSE"; // Admin Account cannot be deleted
        $path= explode('/', $_SERVER['REQUEST_URI']);
        if (isset($path[3]) && is_numeric($path[3])) {
            $statement = $conn->prepare($sql);
            //i = INT
            $statement->bind_param("i", $path[3]);

            if($statement->execute()) {
                $response = ['status'=>1, 'message'=>'Record deleted successfully'];
            } else {
                $response = ['status'=>0, 'message'=>'Failed to delete record'];
            }
            echo json_encode($user[0]);
        }
        break;
}