<?php

    class ReadingSettings extends Connect {

        private $authInstance;

        public function __construct() {
            $this->authInstance = new Auth();
        }

        public function fetchReadingSettings($name, $zone) {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);
            
            $connection = $this->openConnection();

            if ($name === "All") {
                $sql = "SELECT * FROM ReadingSettings";
                $stmt = $connection->prepare($sql);
                $stmt->execute();
            } else {
                $sql = "SELECT * FROM ReadingSettings WHERE name = ? AND zone = ?";
                $stmt = $connection->prepare($sql);
                $stmt->execute([$name, $zone]);
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
