<?php

    class LogicNumber extends Connect {

        public function __construct() {}

        public function fetchLogicNumber($remarks) {
            $connection = $this->openConnection();

            if ($remarks === "All") {
                $sql = "SELECT * FROM [tbllogicnumbers]";
                $stmt = $connection->prepare($sql);
                $stmt->execute();
            } else {
                $sql = "SELECT * FROM [tbllogicnumbers] WHERE remarks = ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([$remarks]);
            }

            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count == 0) {
                return [];
            } else {
                return $result;
            }
        }   

        public function updateLogicNumber($remarks, $value) {
            $connection = $this->openConnection();

            if ($value == 1) {
                $sql = "UPDATE tbllogicnumbers SET number = number + $value WHERE remarks = '$remarks'";
            } else {
                $sql = "UPDATE tbllogicnumbers SET number = $value WHERE remarks = '$remarks'";
            }
            $stmt = $connection->prepare($sql);
            $stmt->execute();

            return $stmt->rowCount();
        }

    }
