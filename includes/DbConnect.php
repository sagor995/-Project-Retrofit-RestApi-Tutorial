<?php

    class DbConnect{
        private $con;

        function connect(){
            include_once dirname(__FILE__).  '/Constants.php';
            
            $this->conn = new mysqli(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME, DB_PORT);

            if(mysqli_connect_errno()){
                echo "Failed to connect".mysqli_connect_error();
                return null;
            }

            return $this->conn;
        }
    }
?>