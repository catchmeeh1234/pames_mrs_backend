<?php

    class MeterReader extends Connect {

        public function __construct()
        {
            
        }

        public function fetchMeterReader($accno) {
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
                return $result;
            }
        }   
    }
