<?php
    class DbConnect {
        private $server = 'localhost';
        private $dbname = ''; //react-crud
        private $user = 'root'; //Username for phpmyadmin
        private $pass = ''; //Password for phpmyadmin

        public function connect($dbname) {
            try {
                #$conn = new PDO('mysql:host=' .$this->server.';dbname='.$this->dbname, $this->user, $this->pass);
                #$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                #$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); 
                $conn =mysqli_connect($this->server,$this->user,$this->pass,$dbname) or die("Error " . mysqli_error($conn));
                return $conn;
            } catch (Exception $e) {
                echo "Database Error: ".$e->getMessage();
            }
        }
    }
?>