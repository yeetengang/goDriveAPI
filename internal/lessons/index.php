<?php

switch($method) {
    //HTTP Method
    case "GET":
        $sql = "SELECT * FROM lessons";
        $path= explode('/', $_SERVER['REQUEST_URI']);
        
        if (isset($path[3]) && is_numeric($path[3])) {
            /* Use to get specific lesson using id */
            $sql .= " WHERE id = ?";
            $statement = $conn->prepare($sql);
            //i = INT
            $statement->bind_param("i", $path[3]);
            $statement->execute();

            $result = $statement->get_result();
            $lesson = array();
            while($row = $result->fetch_assoc()) {
                $lesson[] = $row;
            }
            echo json_encode($lesson[0]);
        } else if ($path[3] == "upcoming") {
            /* Use to get random 3 lessons that are available */
            $sql .= " WHERE slots > 0 ORDER BY RAND() LIMIT 3";
            $statement = $conn->prepare($sql);
            $statement->execute();

            $result = $statement->get_result();
            $lesson = array();
            while($row = $result->fetch_assoc()) {
                $lesson[] = $row;
            }
            echo json_encode($lesson);
        } 
        else {
            /* Use when get all lessons */
            $result = mysqli_query($conn, $sql) or die("Error in Selecting " . mysqli_error($conn));
            $lessons = array();
            while($row =mysqli_fetch_assoc($result))
            {
                $lessons[] = $row;
            }
            echo json_encode($lessons);
        }
        
        break;

    case "POST":
        $lesson = json_decode(file_get_contents('php://input'));
        if ($path[3] == "retrieve") {
            /* Use when get lesson via lesson id (Exp: Get all users that registered for that lesson)*/
            $sql = "SELECT * FROM lessons WHERE lesson_id=? LIMIT 1";

            $statement = $conn->prepare($sql);
            $statement->bind_param("ss", $lesson->id);
            $statement->execute();

            
            $result = $statement->get_result();
            $id = $result->fetch_assoc()['id'];

            // If user authorized then update database for login time
            $sql = "UPDATE users SET login_at=? WHERE id = ?";
            $t=time(); //timestamp when user login
            $statement = $conn->prepare($sql);
            $statement->bind_param('ii', $t, $id);
            $statement->execute();

            $token = apiToken($t);
            $response = ["expire"=>(24 * 60 * 60 * 1000), "token"=>$token]; //expire: time for token to expire (Hours to expire * Minutes * Seconds * 1000)
            echo json_encode($response);
        } else if ($path[3] == "search") {
            /* Use to search lesson via provided details (Date, State, Class)*/
            $date = $lesson->datetime;
            $state = $lesson->state;
            $class = $lesson->class;

            $sql = "SELECT * FROM lessons WHERE";
            if ($date != "") {
                $explodedDate = explode('-', $date);
                $start = $date." 00:00:00";
                $end = $explodedDate[0]."-".$explodedDate[1]."-".((int)$explodedDate[2]+1)." 00:00:00";
                
                $sql .= " start_time BETWEEN '$start' AND '$end'";
            }
            if ($state != "" && $date!="") {
                $sql .= " AND state = '$state'";
            } else if ($state != "" && $date == "") {
                $sql .= " state = '$state'";
            }
            if ($class != "" && ($state != "" || $date!="")) {
                $sql .= " AND type='$class'";
            } else if ($class != "" && $state == "" && $date == ""){
                $sql .= " type='$class'";
            }
            $statement = $conn->prepare($sql);
            $statement->execute();

            $result = $statement->get_result();
            $lesson = array();
            while($row = $result->fetch_assoc()) {
                $lesson[] = $row;
            }
            echo json_encode($lesson);
        }
         else {
            /* Create Lesson */
            $sql = "INSERT INTO lessons(state, venue, type, start_time, end_time, status) VALUES(?, ?, ?, ?, ?, ?)";
            $status = 1;

            $statement = $conn->prepare($sql);
            $statement->bind_param('sssssi', $lesson->state, $lesson->venue, $lesson->type, $lesson->start_time, $lesson->end_time, $status);

            if($statement->execute()) {
                $response = ['status'=>1, 'message'=>'Record created successfully'];
            } else {
                $response = ['status'=>0, 'message'=>'Failed to create record'];
            }
            echo json_encode($response);
        }
        break;

    case "PUT":
        $lesson = json_decode(file_get_contents('php://input'));
        $id = explode('/', $_SERVER['REQUEST_URI'])[3];
        $sql = "UPDATE lessons SET state=?, venue=?, start_time=?, end_time=?, slots=?, status=? WHERE id = ?";
        if ($lesson->status == "ACTIVE") {
            $status = 1;
        } else {
            $status = 0;
        }

        $statement = $conn->prepare($sql);
        $statement->bind_param('ssssiii', $lesson->state, $lesson->venue, $lesson->start_time, $lesson->end_time, $lesson->slots, $status, $id);

        if($statement->execute()) {
            $response = ['status'=>1, 'message'=>'Record updated successfully'];
        } else {
            $response = ['status'=>0, 'message'=>'Failed to update record'];
        }
        
        echo json_encode($response);
        break;

    case "DELETE":
        $sql = "DELETE FROM lessons WHERE id = ?";
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