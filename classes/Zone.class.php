<?php

    class Zone extends Connect {
        private $authInstance;
    
        public function __construct() {
            $this->authInstance = new Auth();
        }

        public function fetchZones() {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);
            $connection = $this->openConnection();

            //PDO query
            $sql = "SELECT * FROM Zones";
            $stmt = $connection->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll();
            $count = $stmt->rowCount();

            if ($count == 0) {
                return [];
            } else {
                return $result;
            }
        }

        public function updateZoneLastNumber($connection, $zone) {
            //validate JWT
            $this->authInstance->validateJWT($_SERVER['HTTP_AUTHORIZATION']);

            $sql = "UPDATE Zones SET LastNumber = LastNumber + 1 WHERE ZoneName = ?";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$zone]);

            if (!$stmt || $stmt->rowCount() <= 0) {
                throw new Exception("Updating Zone's Last number failed: " . $stmt->errorInfo()[2]);
            }
        }
    }
