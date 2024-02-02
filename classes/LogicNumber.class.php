<?php

    class LogicNumber extends Connect {

        private $authInstance;

        public function __construct() {
            $this->authInstance = new Auth();
        }

        public function fetchLogicNumber($remarks) {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

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
             //validate JWT
             $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);
             
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
