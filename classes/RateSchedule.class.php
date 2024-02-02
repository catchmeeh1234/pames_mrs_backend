<?php

    class RateSchedule extends Connect {

        private $authInstance;

        public function __construct() {
            $this->authInstance = new Auth();
        }

        public function fetchRateSchedule($rate_name) {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);
            
            $connection = $this->openConnection();

            if ($rate_name === "All") {
                $sql = "SELECT * FROM RateSchedules";
                $stmt = $connection->prepare($sql);
                $stmt->execute();
            } else {
                $sql = "SELECT * FROM RateSchedules WHERE CustomerType = ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([$rate_name]);
            }

            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count == 0) {
                return [];
            } else {
                return $result;
            }
        }   
    }
