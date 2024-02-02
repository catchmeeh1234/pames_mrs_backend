<?php

    class MeterReader extends Connect {

        private $authInstance;

        public function __construct() {
            $this->authInstance = new Auth();
        }

        public function fetchMeterReader($accno) {
             //validate JWT
             //$this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

            $connection = $this->openConnection();

            if ($accno === "All") {
                $sql = "SELECT * FROM MeterReader";
                $stmt = $connection->prepare($sql);
                $stmt->execute();
            } else {
                $sql = "SELECT * FROM MeterReader WHERE reader_id = ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([$accno]);
            }

            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count == 0) {
                return [];
            } else {
                //convert the result into proper data types
                foreach ($result as &$row) {
                    // Convert 'id' to integer and keep other values unchanged
                    $row['reader_id'] = (int)$row['reader_id'];
                }
                return $result;
            }
        }   
    }
