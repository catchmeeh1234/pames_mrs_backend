<?php

    class RateSchedule extends Connect {

        public function __construct() {}

        public function fetchRateSchedule($rate_name) {
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
